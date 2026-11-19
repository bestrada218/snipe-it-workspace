<x-box :header="trans('asset-replacement.alerts')" class="workspace-replacement-panel">
    <div class="workspace-replacement-windows">
        @foreach ($replacementCounts as $window => $count)
            <a class="workspace-replacement-alert" href="{{ route('hardware.replacement-alerts', ['window' => $window]) }}">
                <span class="workspace-metric-label">{{ trans('asset-replacement.windows.'.$window) }}</span>
                <strong class="workspace-metric-value">{{ number_format($count) }}</strong>
            </a>
        @endforeach
    </div>

    <div class="workspace-replacement-footer">
        <p>{{ trans('asset-replacement.alert_hint') }}</p>
        <a href="{{ route('hardware.replacement-alerts') }}">{{ trans('asset-replacement.view_schedule') }}</a>
    </div>
</x-box>
