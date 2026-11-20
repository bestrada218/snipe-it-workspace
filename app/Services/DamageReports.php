<?php

namespace App\Services;

use App\Enums\ActionType;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Setting;
use App\Models\Statuslabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Moving an asset into an undeployable status (for example "Damage") needs a
 * documented reason: what is wrong, whether it can be repaired and whether a
 * repair was already attempted. The report is stored in the asset history.
 */
final class DamageReports
{
    public const REPAIRABLE = ['yes', 'no', 'unknown'];

    private static bool $bypassed = false;

    /** @var array<int, array<string, mixed>> Reports waiting for the save to finish, by object id. */
    private static array $pending = [];

    /**
     * Run code that changes statuses without a person filling the form
     * (CSV imports, for example).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutReport(callable $callback): mixed
    {
        $previous = self::$bypassed;
        self::$bypassed = true;

        try {
            return $callback();
        } finally {
            self::$bypassed = $previous;
        }
    }

    /**
     * Statuses chosen in General Settings that need a damage report.
     *
     * @return list<int>
     */
    public static function statusIds(): array
    {
        return array_values(array_map('intval', Setting::getSettings()?->damage_report_statuses ?? []));
    }

    public static function isDamagedStatus(?int $statusId): bool
    {
        return $statusId !== null && in_array($statusId, self::statusIds(), true);
    }

    /**
     * Saving hook: an existing asset moving into an undeployable status
     * needs the report in the request; otherwise the save stops.
     */
    public static function require(Asset $asset): void
    {
        if (self::$bypassed || ! auth()->check() || ! $asset->exists || ! $asset->isDirty('status_id')
            || ! self::isDamagedStatus((int) $asset->status_id)) {
            return;
        }

        self::$pending[spl_object_id($asset)] = self::validated(request()) + [
            'status_from' => Statuslabel::find($asset->getOriginal('status_id'))?->name,
            'status_to' => Statuslabel::find($asset->status_id)?->name,
        ];
    }

    /**
     * Saved hook: write the report into the asset history.
     */
    public static function record(Asset $asset): void
    {
        $report = self::$pending[spl_object_id($asset)] ?? null;
        unset(self::$pending[spl_object_id($asset)]);
        if ($report === null) {
            return;
        }

        $log = new Actionlog;
        $log->item_type = Asset::class;
        $log->item_id = $asset->id;
        $log->created_by = auth()->id();
        $log->company_id = $asset->company_id;
        $log->location_id = $asset->location_id;
        $log->note = $report['description'];
        $log->log_meta = json_encode(['_damage_report' => $report], JSON_THROW_ON_ERROR);
        $log->logaction(ActionType::DamageReported);
    }

    /**
     * @return array{description: string, repairable: string, repair_attempted: bool, repair_details: ?string}
     */
    private static function validated(Request $request): array
    {
        $data = Validator::make($request->all(), [
            'damage_description' => ['required', 'string', 'max:2000'],
            'damage_repairable' => ['required', Rule::in(self::REPAIRABLE)],
            'damage_repair_attempted' => ['required', 'boolean'],
            'damage_repair_details' => ['nullable', 'required_if:damage_repair_attempted,1', 'string', 'max:2000'],
        ], [
            'damage_description.required' => trans('damage-reports.description_required'),
        ], [
            'damage_description' => trans('damage-reports.description'),
            'damage_repairable' => trans('damage-reports.repairable'),
            'damage_repair_attempted' => trans('damage-reports.repair_attempted'),
            'damage_repair_details' => trans('damage-reports.repair_details'),
        ])->validate();

        $attempted = filter_var($data['damage_repair_attempted'], FILTER_VALIDATE_BOOLEAN);

        return [
            'description' => trim($data['damage_description']),
            'repairable' => $data['damage_repairable'],
            'repair_attempted' => $attempted,
            'repair_details' => $attempted ? trim((string) $data['damage_repair_details']) : null,
        ];
    }
}
