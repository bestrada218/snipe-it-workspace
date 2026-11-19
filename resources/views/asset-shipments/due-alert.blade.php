@php($dueShipments = \App\Services\AssetShipments::dueFor(auth()->user())->count())
    @if ($dueShipments)
        <x-callout type="warning">
            <a href="{{ route('shipments.index', ['due' => 1]) }}">
                {{ trans_choice('asset-shipments.due_message', $dueShipments) }}
                {{ trans('asset-shipments.due_title') }}
            </a>
        </x-callout>
    @endif
