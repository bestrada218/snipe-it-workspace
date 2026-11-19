<x-input.user-select :label="trans('asset-shipments.recipient')" name="recipient_id" :selected="old('recipient_id')" :company-id="$shipmentCompanyId ?? null" :required="true" :hide-new-button="true" input-column="col-md-8" />
@foreach (['platform' => 'platform', 'carrier' => 'carrier', 'tracking_number' => 'tracking', 'expected_on' => 'expected', 'purchase_approver_id' => 'purchase_approver', 'approval_reference' => 'approval_reference', 'approval' => 'evidence'] as $field => $label)
    @php
        $type = match ($field) {
            'expected_on' => 'date',
            'approval' => 'file',
            default => 'text',
        };
    @endphp
    <div @class(['form-group', 'has-error' => $errors->has($field)])>
        <label class="col-md-3 control-label" for="{{ $field }}">{{ trans('asset-shipments.'.$label) }}</label>
        <div class="col-md-8">
            @if ($field === 'purchase_approver_id')
                <select class="form-control" name="{{ $field }}" id="{{ $field }}" required>
                    <option value="">{{ trans('asset-shipments.choose_purchase_approver') }}</option>
                    @forelse (\App\Services\AssetShipments::purchaseApprovers() as $approver)
                        <option value="{{ $approver->id }}" @selected((int) old($field) === (int) $approver->id)>{{ $approver->display_name }}</option>
                    @empty
                        <option disabled>{{ trans('asset-shipments.no_purchase_approvers') }}</option>
                    @endforelse
                </select>
            @elseif ($field === 'approval_reference')
                <textarea class="form-control" name="{{ $field }}" id="{{ $field }}" rows="3" maxlength="1500" placeholder="{{ trans('asset-shipments.approval_reference_placeholder') }}" required>{{ old($field) }}</textarea>
            @else
                <input class="form-control" type="{{ $type }}" name="{{ $field }}" id="{{ $field }}"
                    @if ($field === 'approval') accept=".pdf,.jpg,.jpeg,.png,.webp" aria-describedby="shipment-approval-help" @else value="{{ old($field) }}" @endif
                    @if (in_array($field, ['platform', 'carrier'], true)) placeholder="{{ trans('asset-shipments.'.$field.'_placeholder') }}" @endif
                    @required($field !== 'carrier')>
            @endif
            @if ($field === 'approval')
                <p class="help-block" id="shipment-approval-help">{{ trans('asset-shipments.evidence_help') }}</p>
            @endif
            <x-form.error :name="$field" />
        </div>
    </div>
@endforeach
