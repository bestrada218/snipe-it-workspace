@extends('layouts/default')

@section('title', trans('asset-returns.number', ['id' => $return->id]))

@php
    $canManage = auth()->user()->can('checkin', \App\Models\Asset::class) && $return->isOpen();
    $stepLabel = fn (string $step) => trans('asset-returns.steps.'.$step, ['partner' => $partner]);
@endphp

@section('content')
    <x-container>
        <x-box :header="trans('asset-returns.number', ['id' => $return->id]).' · '.$return->person?->display_name">
            <span class="workspace-shipment-status" data-status="{{ ['open' => 'in_transit', 'completed' => 'received', 'cancelled' => 'cancelled'][$return->status()] }}">{{ trans('asset-returns.statuses.'.$return->status()) }}</span>
            <dl class="workspace-shipment-details">
                <div><dt>{{ trans('asset-returns.reason') }}</dt><dd>{{ trans('asset-returns.reasons.'.$return->reason) }}</dd></div>
                <div><dt>{{ trans('asset-returns.ticket') }}</dt><dd>{{ $return->ticket_number ?: '—' }}</dd></div>
                <div><dt>{{ trans('asset-returns.ticket_owner') }}</dt><dd>{{ $return->ticketOwner?->display_name ?: '—' }}</dd></div>
                <div><dt>{{ trans('asset-returns.return_owner') }}</dt><dd>{{ $return->returnOwner?->display_name ?: '—' }}</dd></div>
                <div><dt>{{ trans('asset-returns.created_on') }}</dt><dd>{{ Helper::getFormattedDateObject($return->created_at, 'date', false) }}</dd></div>
            </dl>
            <x-form.error name="step" />
            <x-form.error name="return" />

            {{-- Box shipment to the partner warehouse, for the items going back there. --}}
            @if ($return->hasPartnerItems())
                @php($next = $return->nextShippingStep())
                <h3 class="return-section-title">{{ trans('asset-returns.shipping_title', ['partner' => $partner]) }}</h3>
                <ol @class(['shipment-progress', 'is-cancelled' => $return->cancelled_at]) aria-label="{{ trans('asset-returns.shipping_title', ['partner' => $partner]) }}">
                    @foreach (\App\Models\AssetReturn::SHIPPING_STEPS as $step)
                        <li @class(['is-done' => $return->{$step}, 'is-current' => $step === $next && $return->isOpen()]) @if ($step === $next) aria-current="step" @endif>
                            <span class="shipment-progress-label">{{ $stepLabel($step) }}</span>
                            @if ($return->{$step})
                                <span class="shipment-progress-date">{{ Helper::getFormattedDateObject($return->{$step}, 'date', false) }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
                @if ($canManage && $next)
                    <form method="POST" action="{{ route('returns.advance', $return) }}" class="return-step-action">
                        @csrf
                        <button class="btn btn-theme" type="submit">{{ trans('asset-returns.mark', ['step' => $stepLabel($next)]) }}</button>
                    </form>
                @endif
            @endif
        </x-box>

        <x-box :header="trans('asset-returns.items')">
            <x-form.error name="item" />
            <div class="table-responsive">
                <table class="table return-items-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ trans('asset-returns.tag') }}</th>
                            <th scope="col">{{ trans('asset-returns.item') }}</th>
                            <th scope="col">{{ trans('asset-returns.destination') }}</th>
                            <th scope="col">{{ trans('asset-returns.progress') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($return->items as $item)
                            <tr>
                                <td>
                                    @if ($item->item)
                                        <a href="{{ $item->item_type === \App\Models\Asset::class ? route('hardware.show', $item->item_id) : route('accessories.show', $item->item_id) }}"><strong class="return-tag">{{ $item->tag() ?: '—' }}</strong></a>
                                    @endif
                                </td>
                                <td>{{ $item->displayName() }}</td>
                                <td>
                                    @if ($item->goesToPartner())
                                        {{ trans('asset-returns.destinations.partner', ['partner' => $partner]) }}
                                        <span class="return-item-category">{{ trans('asset-returns.previously_with_partner', ['partner' => $partner]) }} {{ $item->previously_with_partner ? trans('asset-returns.yes') : trans('asset-returns.no') }}</span>
                                    @else
                                        {{ trans('asset-returns.storage_location') }}: {{ $item->location?->name }}
                                    @endif
                                    @if ($canManage && ! $item->isCheckedIn())
                                        <details class="return-edit">
                                            <summary>{{ trans('asset-returns.edit') }}</summary>
                                            <form method="POST" action="{{ route('returns.items.update', [$return, $item]) }}" data-return-item>
                                                @csrf
                                                @method('PUT')
                                                <select class="form-control" name="destination" data-return-destination aria-label="{{ trans('asset-returns.destination') }}">
                                                    @foreach (\App\Models\AssetReturnItem::DESTINATIONS as $option)
                                                        <option value="{{ $option }}" @selected($item->destination === $option)>{{ trans('asset-returns.destinations.'.$option, ['partner' => $partner]) }}</option>
                                                    @endforeach
                                                </select>
                                                <div data-when="partner">
                                                    <span class="return-question">{{ trans('asset-returns.previously_with_partner', ['partner' => $partner]) }}</span>
                                                    @foreach (['1' => 'yes', '0' => 'no'] as $value => $text)
                                                        <label><input type="radio" name="previously_with_partner" value="{{ $value }}" @checked($item->previously_with_partner !== null && (int) $item->previously_with_partner === (int) $value)> {{ trans('asset-returns.'.$text) }}</label>
                                                    @endforeach
                                                </div>
                                                <div data-when="storage">
                                                    <select class="form-control" name="location_id" aria-label="{{ trans('asset-returns.storage_location') }}">
                                                        <option value="">{{ trans('asset-returns.choose_location') }}</option>
                                                        @foreach ($storageLocations as $location)
                                                            <option value="{{ $location->id }}" @selected($item->location_id === $location->id)>{{ $location->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button class="btn btn-default btn-sm" type="submit">{{ trans('general.save') }}</button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->goesToPartner())
                                        @php($itemSteps = $item->needsPartnerSetup() ? ['partner_received_at', 'partner_configured_at'] : [])
                                        <ol class="shipment-progress return-item-progress">
                                            <li @class(['is-done' => $return->warehouse_arrived_at, 'is-current' => ! $return->warehouse_arrived_at])>
                                                <span class="shipment-progress-label">{{ $return->warehouse_arrived_at ? trans('asset-returns.at_partner', ['partner' => $partner]) : trans('asset-returns.waiting_arrival') }}</span>
                                            </li>
                                            @foreach ($itemSteps as $step)
                                                <li @class(['is-done' => $item->{$step}, 'is-current' => $item->nextPartnerStep() === $step])>
                                                    <span class="shipment-progress-label">{{ $stepLabel($step) }}</span>
                                                    @if ($item->{$step})
                                                        <span class="shipment-progress-date">{{ Helper::getFormattedDateObject($item->{$step}, 'date', false) }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ol>
                                        @if ($canManage && $item->nextPartnerStep())
                                            <form method="POST" action="{{ route('returns.items.advance', [$return, $item]) }}">
                                                @csrf
                                                <button class="btn btn-default btn-sm" type="submit">{{ trans('asset-returns.mark', ['step' => $stepLabel($item->nextPartnerStep())]) }}</button>
                                            </form>
                                        @endif
                                    @else
                                        @if ($item->stored_at)
                                            <span class="workspace-shipment-status" data-status="received">{{ trans('asset-returns.stored_at_location', ['location' => $item->location?->name]) }}</span>
                                        @else
                                            <span class="workspace-shipment-status" data-status="in_transit">{{ trans('asset-returns.pending_store', ['location' => $item->location?->name]) }}</span>
                                            @if ($canManage)
                                                <form method="POST" action="{{ route('returns.items.store', [$return, $item]) }}">
                                                    @csrf
                                                    <button class="btn btn-default btn-sm" type="submit">{{ trans('asset-returns.store') }}</button>
                                                </form>
                                            @endif
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-box>

        {{-- Notes are copied to the history of every returned item. --}}
        <x-box :header="trans('asset-returns.notes')" id="notes">
            <p class="help-block">{{ trans('asset-returns.notes_help') }}</p>
            @can('checkin', \App\Models\Asset::class)
                <form method="POST" action="{{ route('returns.notes', $return) }}" class="return-note-form">
                    @csrf
                    <label for="return-note" class="sr-only">{{ trans('asset-returns.add_note') }}</label>
                    <textarea class="form-control" id="return-note" name="note" rows="3" maxlength="5000" required placeholder="{{ trans('asset-returns.note_placeholder') }}">{{ old('note') }}</textarea>
                    <x-form.error name="note" />
                    <button class="btn btn-theme" type="submit">{{ trans('asset-returns.add_note') }}</button>
                </form>
            @endcan
            <ul class="return-notes">
                @forelse ($return->notes as $note)
                    <li>
                        <p>{{ $note->note }}</p>
                        <span class="return-item-category">{{ $note->author?->display_name }} · {{ Helper::getFormattedDateObject($note->created_at, 'datetime', false) }}</span>
                    </li>
                @empty
                    <li class="text-muted">{{ trans('asset-returns.no_notes') }}</li>
                @endforelse
            </ul>
        </x-box>

        @if ($canManage && $return->canBeCancelled())
            <form method="POST" action="{{ route('returns.cancel', $return) }}" onsubmit="return confirm(@js(trans('asset-returns.cancel_confirm')))">
                @csrf
                <button class="btn btn-danger" type="submit">{{ trans('asset-returns.cancel') }}</button>
            </form>
        @endif
    </x-container>
@stop

@push('js')
    <script nonce="{{ csrf_token() }}">
        document.querySelectorAll('[data-return-item]').forEach(form => {
            const select = form.querySelector('[data-return-destination]');
            const sync = () => form.querySelectorAll('[data-when]').forEach(part => {
                part.hidden = part.dataset.when !== select.value;
            });
            select.addEventListener('change', sync);
            sync();
        });
    </script>
@endpush
