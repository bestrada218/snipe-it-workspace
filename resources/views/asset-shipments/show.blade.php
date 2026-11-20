@extends('layouts/default')

@section('title', trans('asset-shipments.details'))

@section('content')
    <x-container>
        <x-box :header="$shipment->asset->display_name">
            <span class="workspace-shipment-status" data-status="{{ $shipment->status() }}">{{ trans('asset-shipments.'.$shipment->status()) }}</span>
            <dl class="workspace-shipment-details">
                <div><dt>{{ trans('asset-shipments.recipient') }}</dt><dd>{{ $shipment->recipient?->display_name }}</dd></div>
                <div><dt>{{ trans('asset-shipments.platform') }}</dt><dd>{{ $shipment->platform }}</dd></div>
                @if ($shipment->carrier)
                    <div><dt>{{ trans('asset-shipments.carrier') }}</dt><dd>{{ $shipment->carrier }}</dd></div>
                @endif
                <div><dt>{{ trans('asset-shipments.tracking') }}</dt><dd>{{ $shipment->tracking_number }}</dd></div>
                <div><dt>{{ trans('asset-shipments.expected') }}</dt><dd>{{ Helper::getFormattedDateObject($shipment->expected_on, 'date', false) }}</dd></div>
                @if ($shipment->received_at)
                    <div><dt>{{ trans('asset-shipments.received_at') }}</dt><dd>{{ Helper::getFormattedDateObject($shipment->received_at, 'datetime', false) }}</dd></div>
                @endif
                @if ($canViewApproval)
                    @if ($shipment->purchase_approver_name)
                        <div><dt>{{ trans('asset-shipments.purchase_approver') }}</dt><dd>{{ $shipment->purchase_approver_name }}</dd></div>
                    @endif
                    <div><dt>{{ trans('asset-shipments.approval_reference') }}</dt><dd class="workspace-shipment-note">{{ $shipment->approval_reference }}</dd></div>
                    <div><dt>{{ trans('asset-shipments.evidence') }}</dt><dd><a href="{{ route('shipments.approval', $shipment) }}">{{ $shipment->approval_filename }}</a></dd></div>
                @endif
            </dl>
            <x-form.error name="asset" />

            @if ($shipment->isOpen() && ! $shipment->received_at)
                @php($isRecipient = (int) auth()->id() === (int) $shipment->recipient_id)
                @if ($isRecipient)
                    <form method="POST" action="{{ route('shipments.receive', $shipment) }}" class="workspace-shipment-action">
                        @csrf
                        <p>{{ trans('asset-shipments.receive_help') }}</p>
                        <button class="btn btn-theme" type="submit">{{ trans('asset-shipments.receive') }}</button>
                    </form>
                @elseif ($canManage)
                    <details class="workspace-shipment-action workspace-shipment-receipt" @if ($errors->has('receipt_note')) open @endif>
                        <summary class="btn btn-theme">{{ trans('asset-shipments.confirm_delivery') }}</summary>
                        <form method="POST" action="{{ route('shipments.receive', $shipment) }}">
                            @csrf
                            <p>{{ trans('asset-shipments.confirm_on_behalf', ['name' => $shipment->recipient?->display_name]) }}</p>
                            <label for="shipment-receipt-note">{{ trans('asset-shipments.receipt_note') }}</label>
                            <textarea class="form-control" name="receipt_note" id="shipment-receipt-note" rows="3" maxlength="1000" placeholder="{{ trans('asset-shipments.receipt_note_placeholder') }}" required>{{ old('receipt_note') }}</textarea>
                            <x-form.error name="receipt_note" />
                            <button class="btn btn-theme" type="submit">{{ trans('asset-shipments.save_receipt') }}</button>
                        </form>
                    </details>
                @endif
                @if ($canManage || $isRecipient)
                    <details class="workspace-shipment-action" @if ($errors->has('expected_on')) open @endif>
                        <summary>{{ trans('asset-shipments.reschedule') }}</summary>
                        <form method="POST" action="{{ route('shipments.reschedule', $shipment) }}">
                            @csrf
                            <label for="shipment-date">{{ trans('asset-shipments.expected') }}</label>
                            <input class="form-control" type="date" id="shipment-date" name="expected_on" value="{{ old('expected_on') }}" min="{{ today()->toDateString() }}" required>
                            <x-form.error name="expected_on" />
                            <label for="shipment-reschedule-reason">{{ trans('asset-shipments.reason') }}</label>
                            <input class="form-control" type="text" id="shipment-reschedule-reason" name="reason" maxlength="1000" placeholder="{{ trans('asset-shipments.reschedule_reason_placeholder') }}" required>
                            <x-form.error name="reason" />
                            <button class="btn btn-theme" type="submit">{{ trans('asset-shipments.save_date') }}</button>
                        </form>
                    </details>
                @endif
                @if ($canManage)
                    <details class="workspace-shipment-action">
                        <summary>{{ trans('asset-shipments.cancel') }}</summary>
                        <form method="POST" action="{{ route('shipments.cancel', $shipment) }}">
                            @csrf
                            <label for="shipment-cancel-reason">{{ trans('asset-shipments.reason') }}</label>
                            <input class="form-control" type="text" id="shipment-cancel-reason" name="reason" maxlength="1000" placeholder="{{ trans('asset-shipments.cancel_reason_placeholder') }}" required>
                            <button class="btn btn-danger" type="submit">{{ trans('asset-shipments.cancel') }}</button>
                        </form>
                    </details>
                @endif
            @elseif ($shipment->isOpen() && $shipment->received_at && $canManage)
                <form method="POST" enctype="multipart/form-data" action="{{ route('shipments.complete', $shipment) }}" class="workspace-shipment-action">
                    @csrf
                    @if ($duplicate)
                        <p>{{ trans('asset-shipments.duplicate_hint') }}</p>
                        <input type="hidden" name="duplicate_assignment_confirmed" value="1">
                        <label for="shipment-duplicate-reason">{{ trans('duplicate-assignments.reason') }}</label>
                        <textarea class="form-control" id="shipment-duplicate-reason" name="duplicate_assignment_reason" maxlength="1000" rows="3" placeholder="{{ trans('asset-shipments.duplicate_reason_placeholder') }}" required>{{ old('duplicate_assignment_reason') }}</textarea>
                        <label for="shipment-duplicate-ticket">{{ trans('duplicate-assignments.ticket') }}</label>
                        <input class="form-control" type="text" id="shipment-duplicate-ticket" name="duplicate_assignment_ticket" maxlength="100" value="{{ old('duplicate_assignment_ticket') }}" placeholder="{{ trans('duplicate-assignments.ticket_placeholder') }}" required>
                        @if ($duplicateNeedsApproval)
                        <label for="shipment-duplicate-approver">{{ trans('duplicate-assignments.approved_by') }}</label>
                        <select class="form-control" id="shipment-duplicate-approver" name="duplicate_assignment_approver" required>
                            <option value="">{{ trans('duplicate-assignments.select_approver') }}</option>
                            @forelse (\App\Services\DuplicateAssignments::approvers() as $approver)
                                <option value="{{ $approver->id }}" @selected((int) old('duplicate_assignment_approver') === $approver->id)>{{ $approver->display_name }}</option>
                            @empty
                                <option disabled>{{ trans('duplicate-assignments.no_approvers') }}</option>
                            @endforelse
                        </select>
                        <label for="shipment-duplicate-evidence">{{ trans('duplicate-assignments.evidence') }}</label>
                        <input class="form-control" type="file" name="duplicate_assignment_evidence" id="shipment-duplicate-evidence" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                        @endif
                    @endif
                    <x-form.error name="duplicate_assignment_reason" />
                    <x-form.error name="duplicate_assignment_ticket" />
                    <x-form.error name="duplicate_assignment_approver" />
                    <x-form.error name="duplicate_assignment_evidence" />
                    <button class="btn btn-theme" type="submit">{{ trans('asset-shipments.complete') }}</button>
                </form>
            @endif
        </x-box>
    </x-container>
@stop
