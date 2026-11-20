<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Consumable;
use App\Models\License;
use App\Models\User;
use App\Services\DuplicateAssignments;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DuplicateAssignmentsController extends Controller
{
    public function index(): View
    {
        abort_unless(DuplicateAssignments::canViewAlerts(), 403);

        $alerts = DuplicateAssignments::alerts()
            ->orderByDesc('units')
            ->orderBy('category')
            ->orderBy('person_id')
            ->paginate(25);
        $people = User::whereKey($alerts->pluck('person_id')->unique()->all())->get()->keyBy('id');

        $members = DuplicateAssignments::members(
            $people->keys()->all(),
            $alerts->pluck('category_id')->unique()->all(),
        );
        $items = $this->itemsFor($members);
        $approvals = $this->approvalsFor($members, $items);
        $membersByGroup = $members->groupBy(fn ($member) => $member->person_id.':'.$member->category_id);

        return view('duplicate-assignments.index', compact('alerts', 'people', 'membersByGroup', 'items', 'approvals'));
    }

    private function itemsFor(Collection $members): Collection
    {
        $items = collect();
        foreach ($members->groupBy('item_type') as $type => $rows) {
            $query = $type::query()->whereKey($rows->pluck('item_id')->unique()->all());
            if ($type === Asset::class) {
                $query->with('model');
            }

            foreach ($query->get() as $item) {
                if (Gate::allows('view', $item)) {
                    $items->put($type.':'.$item->id, $item);
                }
            }
        }

        return $items;
    }

    private function approvalsFor(Collection $members, Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        return Actionlog::query()
            ->where('action_type', 'checkout')
            ->where('target_type', User::class)
            ->whereIn('target_id', $members->pluck('person_id')->unique())
            ->where(function ($query) use ($items) {
                foreach ($items->groupBy(fn ($item) => $item::class) as $type => $ofType) {
                    $query->orWhere(fn ($subquery) => $subquery->where('item_type', $type)
                        ->whereIn('item_id', $ofType->pluck('id')));
                }
            })
            ->where(fn ($query) => $query->where('item_type', Asset::class)
                ->orWhere('log_meta', 'like', '%"_duplicate_assignment_reason"%'))
            ->latest('created_at')->orderByDesc('id')->get()
            ->filter(function (Actionlog $log) use ($items): bool {
                $log->setRelation('item', $items->get($log->item_type.':'.$log->item_id));

                return DuplicateAssignments::canViewApproval($log);
            })
            ->groupBy(fn (Actionlog $log) => $log->target_id.':'.$log->item_type.':'.$log->item_id);
    }

    public static function itemUrl(Asset|Accessory|Consumable|License $item): string
    {
        return route(match ($item::class) {
            Asset::class => 'hardware.show',
            Accessory::class => 'accessories.show',
            Consumable::class => 'consumables.show',
            License::class => 'licenses.show',
        }, $item);
    }

    public function evidence(int $id): StreamedResponse
    {
        $log = Actionlog::query()->with('item')->where('action_type', 'checkout')->findOrFail($id);
        abort_unless(DuplicateAssignments::canViewApproval($log), 403);

        $approval = json_decode($log->log_meta ?? '{}', true)['_duplicate_assignment_approval'] ?? [];
        $path = DuplicateAssignments::evidencePath($approval);
        abort_unless($path && Storage::exists($path), 404);

        return Storage::download($path, $approval['filename'], [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Duplicate check for the asset create form, where the asset does not
     * exist yet: the selected model decides the category.
     */
    public function previewModel(Request $request, int $model): JsonResponse
    {
        $this->authorize('create', Asset::class);
        $this->authorize('checkout', Asset::class);
        $input = $request->validate([
            'user' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $person = User::findOrFail($input['user']);

        $asset = new Asset;
        $asset->setRelation('model', AssetModel::with('category')->findOrFail($model));

        return response()->json(DuplicateAssignments::inspect($asset, $person, (int) $input['quantity']));
    }

    public function preview(Request $request, string $type, int $id): JsonResponse
    {
        $class = DuplicateAssignments::TYPES[$type] ?? null;
        abort_unless($class, 404);

        $item = $class::findOrFail($id);
        $this->authorize('checkout', $item);
        $input = $request->validate([
            'user' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $person = User::findOrFail($input['user']);
        abort_unless($item->canCheckoutTo($person), 403);

        return response()->json(DuplicateAssignments::inspect($item, $person, (int) $input['quantity']));
    }
}
