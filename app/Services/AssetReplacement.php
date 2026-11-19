<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AssetReplacement
{
    public static function attributes(Request $request, ?Asset $asset = null): array
    {
        if (! $request->has('replacement_required')) {
            return [];
        }

        $request->validate(['replacement_required' => 'required|boolean']);
        if (! $request->boolean('replacement_required')) {
            return ['replacement_due_on' => null, 'replacement_years' => null];
        }

        $input = $request->validate([
            'replacement_mode' => 'required|in:years,date',
            'replacement_years' => 'required_if:replacement_mode,years|nullable|integer|min:1|max:100',
            'replacement_due_on' => 'required_if:replacement_mode,date|nullable|date_format:Y-m-d',
        ]);

        if ($input['replacement_mode'] === 'years') {
            $years = (int) $input['replacement_years'];
            $registered = $asset?->created_at?->toDateString() ?? CarbonImmutable::today()->toDateString();

            return [
                'replacement_due_on' => CarbonImmutable::parse($registered)->addYearsNoOverflow($years)->toDateString(),
                'replacement_years' => $years,
            ];
        }

        $date = $input['replacement_due_on'];
        if ($date <= CarbonImmutable::today()->toDateString() && $date !== $asset?->replacement_due_on?->toDateString()) {
            throw ValidationException::withMessages(['replacement_due_on' => trans('asset-replacement.future_date')]);
        }

        return ['replacement_due_on' => $date, 'replacement_years' => null];
    }

    /**
     * Each asset belongs to a single window, using calendar months.
     *
     * @return array<string, array{0: ?string, 1: string}>
     */
    public static function windows(): array
    {
        $today = CarbonImmutable::today();
        $oneDay = $today->addDay()->toDateString();
        $fifteenDays = $today->addDays(15)->toDateString();
        $oneMonth = $today->addMonthNoOverflow()->toDateString();

        return [
            'overdue' => [null, $today->subDay()->toDateString()],
            'one_day' => [$today->toDateString(), $oneDay],
            'fifteen_days' => [$today->addDays(2)->toDateString(), $fifteenDays],
            'one_month' => [$today->addDays(16)->toDateString(), $oneMonth],
            'three_months' => [CarbonImmutable::parse($oneMonth)->addDay()->toDateString(), $today->addMonthsNoOverflow(3)->toDateString()],
        ];
    }

    public static function query(?array $window = null): Builder
    {
        $query = Asset::query()->notArchived()->whereNotNull('assets.replacement_due_on');

        if ($window !== null) {
            [$from, $until] = $window;
            $query->where('assets.replacement_due_on', '<=', $until)
                ->when($from !== null, fn (Builder $assets) => $assets->where('assets.replacement_due_on', '>=', $from));
        }

        return $query;
    }
}
