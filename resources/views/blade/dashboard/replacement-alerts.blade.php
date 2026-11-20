@php($highest = max(1, ...array_values($replacementCounts)))

<x-box :header="trans('asset-replacement.alerts')" class="workspace-replacement-panel">
    <div class="replacement-bars">
        @foreach ($replacementCounts as $window => $count)
            <a @class(['replacement-bar-row', 'is-empty' => $count === 0, 'is-overdue' => $window === 'overdue'])
               href="{{ route('hardware.replacement-alerts', ['window' => $window]) }}">
                <span class="replacement-bar-label">{{ trans('asset-replacement.windows.'.$window) }}</span>
                <span class="replacement-bar-track" aria-hidden="true">
                    <span class="replacement-bar-fill" style="width: {{ $count === 0 ? 0 : max(4, round($count / $highest * 100)) }}%"></span>
                </span>
                <strong class="replacement-bar-count">{{ number_format($count) }}</strong>
            </a>
        @endforeach
    </div>

    <div class="workspace-replacement-footer">
        <p>{{ trans('asset-replacement.alert_hint') }}</p>
        <a href="{{ route('hardware.replacement-alerts') }}">{{ trans('asset-replacement.view_schedule') }}</a>
    </div>
</x-box>
