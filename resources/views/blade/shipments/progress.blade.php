@props(['shipment'])

@php
    $cancelled = $shipment->cancelled_at !== null;
    $steps = [
        ['label' => trans('asset-shipments.step_registered'), 'done' => true, 'date' => $shipment->created_at],
        ['label' => trans('asset-shipments.step_in_transit'), 'done' => $shipment->received_at !== null, 'date' => null],
        ['label' => trans('asset-shipments.step_received'), 'done' => $shipment->received_at !== null, 'date' => $shipment->received_at],
        ['label' => trans('asset-shipments.step_assigned'), 'done' => $shipment->assigned_at !== null, 'date' => $shipment->assigned_at],
    ];
    $current = $cancelled ? null : collect($steps)->search(fn ($step) => ! $step['done']);
@endphp

{{-- Registered → In transit → Received → Assigned; a cancelled shipment stops where it was. --}}
<ol @class(['shipment-progress', 'is-cancelled' => $cancelled]) aria-label="{{ trans('asset-shipments.progress') }}">
    @foreach ($steps as $index => $step)
        <li @class(['is-done' => $step['done'], 'is-current' => $current === $index]) @if ($current === $index) aria-current="step" @endif>
            <span class="shipment-progress-label">{{ $step['label'] }}</span>
            @if ($step['date'])
                <span class="shipment-progress-date">{{ Helper::getFormattedDateObject($step['date'], 'date', false) }}</span>
            @elseif ($index === 1 && ! $step['done'] && ! $cancelled)
                <span class="shipment-progress-date">{{ trans('asset-shipments.expected_short', ['date' => Helper::getFormattedDateObject($shipment->expected_on, 'date', false)]) }}</span>
            @endif
        </li>
    @endforeach
</ol>
@if ($cancelled)
    <p class="shipment-progress-cancelled">{{ trans('asset-shipments.cancelled_on', ['date' => Helper::getFormattedDateObject($shipment->cancelled_at, 'date', false)]) }}</p>
@endif
