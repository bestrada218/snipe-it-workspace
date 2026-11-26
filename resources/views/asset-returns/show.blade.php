@extends('layouts/default')

@section('title', trans('asset-returns.number', ['id' => $return->id]))

@php
    $canManage = $canUpdate && $return->isOpen();
    // A partner step marked by mistake can be undone even after the return completed.
    $canUndoItems = $canUpdate && $return->cancelled_at === null;
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
                @if ($return->hasPartnerItems())
                    <div><dt>{{ trans('asset-returns.carrier') }}</dt><dd>{{ $return->carrier ?: '—' }}</dd></div>
                    <div><dt>{{ trans('asset-returns.tracking_number') }}</dt><dd>{{ $return->tracking_number ?: '—' }}</dd></div>
                @endif
            </dl>

            @if ($canManage)
                <details class="return-details-edit" @if ($errors->hasAny(['reason', 'ticket_number', 'ticket_owner_id', 'return_owner_id', 'carrier', 'tracking_number'])) open @endif>
                    <summary>{{ trans('asset-returns.edit_details') }}</summary>
                    <form method="POST" action="{{ route('returns.update', $return) }}" class="form-horizontal">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ trans('asset-returns.reason') }}</label>
                            <div class="col-md-7 return-reasons">
                                @foreach (\App\Models\AssetReturn::REASONS as $reason)
                                    <label><input type="radio" name="reason" value="{{ $reason }}" @checked(old('reason', $return->reason) === $reason)> {{ trans('asset-returns.reasons.'.$reason) }}</label>
                                @endforeach
                            </div>
                        </div>
                        <x-form.row :label="trans('asset-returns.ticket')" name="ticket_number" :item="$return" :required="false" :placeholder="trans('asset-returns.ticket_placeholder')" maxlength="100" />
                        <x-input.user-select :label="trans('asset-returns.ticket_owner')" name="ticket_owner_id" :selected="old('ticket_owner_id', $return->ticket_owner_id)" />
                        <x-input.user-select :label="trans('asset-returns.return_owner')" name="return_owner_id" :selected="old('return_owner_id', $return->return_owner_id)" />
                        @if ($return->hasPartnerItems())
                            <x-form.row :label="trans('asset-returns.carrier')" name="carrier" :item="$return" :required="false" :placeholder="trans('asset-returns.carrier_placeholder')" maxlength="100" />
                            <x-form.row :label="trans('asset-returns.tracking_number')" name="tracking_number" :item="$return" :required="false" maxlength="100" />
                        @endif
                        <div class="form-group">
                            <div class="col-md-7 col-md-offset-3">
                                <button class="btn btn-theme" type="submit">{{ trans('general.save') }}</button>
                            </div>
                        </div>
                    </form>
                </details>
            @endif
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
                @if ($canManage)
                    <div class="return-step-actions">
                        @if ($next)
                            @php($pending = array_slice(\App\Models\AssetReturn::SHIPPING_STEPS, array_search($next, \App\Models\AssetReturn::SHIPPING_STEPS)))
                            <form method="POST" action="{{ route('returns.advance', $return) }}" class="return-step-form">
                                @csrf
                                <label>
                                    <span>{{ trans('asset-returns.what_happened') }}</span>
                                    <select class="form-control" name="step">
                                        @foreach ($pending as $step)
                                            <option value="{{ $step }}">{{ $stepLabel($step) }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>
                                    <span>{{ trans('asset-returns.date') }}</span>
                                    <input class="form-control" type="date" name="date" value="{{ now()->toDateString() }}" min="{{ $return->created_at->toDateString() }}" max="{{ now()->toDateString() }}">
                                </label>
                                <button class="btn btn-theme" type="submit">{{ trans('asset-returns.record') }}</button>
                            </form>
                        @endif
                        @if (($last = $return->lastShippingStep()) && $last !== 'warehouse_arrived_at')
                            <form method="POST" action="{{ route('returns.undo', $return) }}" class="return-undo-form">
                                @csrf
                                <button class="btn btn-link return-undo" type="submit"><x-icon type="undo" /> {{ trans('asset-returns.undo', ['step' => $stepLabel($last)]) }}</button>
                            </form>
                        @endif
                        @if ($next)
                            <p class="return-step-help">{{ trans('asset-returns.record_help', ['partner' => $partner]) }}</p>
                        @endif
                    </div>
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
                                                    <x-input.warehouse-location-select name="location_id" :tree="$ownWarehouseTree" :selected="$item->location_id" :aria-label="trans('asset-returns.storage_location')" />
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
                                        @if ($canManage && ($itemNext = $item->nextPartnerStep()))
                                            <form method="POST" action="{{ route('returns.items.advance', [$return, $item]) }}" class="return-step-form return-step-form-sm">
                                                @csrf
                                                <select class="form-control input-sm" name="step" aria-label="{{ trans('asset-returns.what_happened') }}">
                                                    @foreach (array_slice(\App\Models\AssetReturnItem::PARTNER_STEPS, array_search($itemNext, \App\Models\AssetReturnItem::PARTNER_STEPS)) as $step)
                                                        <option value="{{ $step }}">{{ $stepLabel($step) }}</option>
                                                    @endforeach
                                                </select>
                                                <input class="form-control input-sm" type="date" name="date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="{{ trans('asset-returns.date') }}" required>
                                                <button class="btn btn-default btn-sm" type="submit">{{ trans('asset-returns.record') }}</button>
                                            </form>
                                        @endif
                                        @if ($canUndoItems && ($itemLast = $item->lastPartnerStep()))
                                            <form method="POST" action="{{ route('returns.items.undo', [$return, $item]) }}">
                                                @csrf
                                                <button class="btn btn-link return-undo" type="submit"><x-icon type="undo" /> {{ trans('asset-returns.undo', ['step' => $stepLabel($itemLast)]) }}</button>
                                            </form>
                                        @endif
                                    @else
                                        @if ($item->stored_at)
                                            <span class="workspace-shipment-status" data-status="received">{{ trans('asset-returns.stored_at_location', ['location' => $item->location?->name]) }}</span>
                                        @else
                                            <span class="workspace-shipment-status" data-status="in_transit">{{ trans('asset-returns.pending_store', ['location' => $item->location?->name]) }}</span>
                                            @if ($canManage)
                                                <form method="POST" action="{{ route('returns.items.store', [$return, $item]) }}" class="return-step-form return-step-form-sm">
                                                    @csrf
                                                    <input class="form-control input-sm" type="date" name="date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="{{ trans('asset-returns.date') }}" required>
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
            @if ($canUpdate)
                <form method="POST" action="{{ route('returns.notes', $return) }}" class="return-note-form">
                    @csrf
                    <label for="return-note" class="sr-only">{{ trans('asset-returns.add_note') }}</label>
                    <textarea class="form-control" id="return-note" name="note" rows="3" maxlength="5000" required placeholder="{{ trans('asset-returns.note_placeholder') }}">{{ old('note') }}</textarea>
                    <x-form.error name="note" />
                    <button class="btn btn-theme" type="submit">{{ trans('asset-returns.add_note') }}</button>
                </form>
            @endif
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
