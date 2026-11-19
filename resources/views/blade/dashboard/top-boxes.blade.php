@props(['counts', 'fixedAssetMetric' => null])

@php
    $metrics = [
        ['hardware.index', 'assets', 'assets', \App\Models\Asset::AssetsForShow()->count()],
        ['licenses.index', 'licenses', 'licenses', $counts['license']],
        ['accessories.index', 'accessories', 'accessories', $counts['accessory']],
        ['consumables.index', 'consumables', 'consumables', $counts['consumable']],
        ['components.index', 'components', 'components', $counts['component']],
        ['users.index', 'users', 'people', $counts['user']],
    ];
@endphp

<div class="workspace-metrics">
    @foreach ($metrics as [$route, $icon, $label, $count])
        @continue(! \App\Models\Setting::inventoryModuleEnabled($label))
        <a class="workspace-metric" href="{{ route($route) }}">
            <span class="workspace-metric-icon" aria-hidden="true"><x-icon :type="$icon" /></span>
            <span class="workspace-metric-label">{{ \App\Models\Setting::inventoryModuleLabel($label) }}</span>
            <strong class="workspace-metric-value">{{ number_format($count) }}</strong>
        </a>
    @endforeach
</div>

@if ($fixedAssetMetric)
    <a class="workspace-fixed-metric" href="{{ route('hardware.index', ['inventory_view' => 'fixed-assets']) }}">
        <span class="workspace-metric-icon" aria-hidden="true"><x-icon type="location" /></span>
        <span class="workspace-fixed-metric-label">{{ $fixedAssetMetric['label'] }}</span>
        <strong class="workspace-metric-value">{{ number_format($fixedAssetMetric['count']) }}</strong>
        <x-icon type="angle-right" />
    </a>
@endif
