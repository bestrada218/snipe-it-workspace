@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('admin/accessories/general.checkout') }}
@parent
@stop

@section('header_right')
    <a href="{{ URL::previous() }}" class="btn btn-primary pull-right">{{ trans('general.back') }}</a>
@stop

{{-- Page content --}}
@section('content')

<x-container columns="2">
    <x-page-column class="col-md-7">

        <x-form route="{{ url()->current() }}" id="checkout_form">

            <x-box header="{{ $accessory->asset_tag ?: $accessory->name }}">

            <x-checkout.asset-tag :tag="$accessory->asset_tag" />

            @if ($accessory->name)
                <x-form.static :label="trans('admin/accessories/general.accessory_name')">{{ $accessory->name }}</x-form.static>
            @endif

            @if ($accessory->company)
                <x-form.static :label="trans('general.company')">{!! $accessory->company->present()->formattedNameLink !!}</x-form.static>
            @endif

            @if ($accessory->category)
                <x-form.static :label="trans('general.category')">{!! $accessory->category->present()->formattedNameLink !!}</x-form.static>
            @endif

            <x-form.static :label="trans('admin/components/general.total')">{{ $accessory->qty }}</x-form.static>

            <x-form.static :label="trans('admin/components/general.remaining')">{{ $accessory->numRemaining() }}</x-form.static>

            @include ('partials.forms.checkout-selector', ['user_select' => 'true', 'asset_select' => 'true', 'location_select' => 'true'])
            <x-input.user-select
                :label="trans('general.user')"
                name="assigned_user"
                :selected="old('assigned_user')"
                :companyId="$accessory->company_id"
                :style="(session('checkout_to_type') ?: 'user') == 'user' ? null : 'display: none;'"
            />
            <x-checkout.duplicate-assignment type="accessories" :item="$accessory" />
            @include ('partials.forms.edit.asset-select', ['translated_name' => trans('general.asset'), 'asset_selector_div_id' => 'assigned_asset', 'company_id' => $accessory->company_id, 'fieldname' => 'assigned_asset', 'unselect' => 'true', 'style' => session('checkout_to_type') == 'asset' ? '' : 'display: none;'])
            @include ('partials.forms.edit.location-select', ['translated_name' => trans('general.location'), 'fieldname' => 'assigned_location', 'company_id' => $accessory->company_id, 'style' => session('checkout_to_type') == 'location' ? '' : 'display: none;'])

            {{-- Each accessory is picked by its own tag, so a checkout is always one unit. --}}
            <input type="hidden" name="checkout_qty" value="1">
            @error('checkout_qty')
                <div class="form-group"><div class="col-md-8 col-md-offset-3"><x-form.error name="checkout_qty" /></div></div>
            @enderror

            <x-checkout.checkout-notification-callout :item="$accessory" :category="$accessory->category" />

            <x-form.row
                :label="trans('admin/hardware/form.notes')"
                :item="$accessory"
                name="note"
                type="textarea"
            />

            <x-slot:customfooter>
                <x-redirect_submit_options
                    index_route="accessories.index"
                    :button_label="trans('general.checkout')"
                    :options="[
                        'index' => trans('admin/hardware/form.redirect_to_all', ['type' => trans('general.accessories')]),
                        'item' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.accessory')]),
                        'target' => trans('admin/hardware/form.redirect_to_checked_out_to'),
                    ]"
                />
            </x-slot:customfooter>

            </x-box>

        </x-form>

    </x-page-column>

    <x-page-column class="col-md-5">
        <livewire:checkout-target-panel type="accessories" />
    </x-page-column>

</x-container>

@stop
