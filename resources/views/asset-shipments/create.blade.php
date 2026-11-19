@extends('layouts/default')

@section('title', trans('asset-shipments.create'))

@section('content')
    <x-container>
        <x-box :header="$asset?->display_name ?? trans('asset-shipments.create')">
            <p class="text-muted">{{ trans('asset-shipments.create_help') }}</p>
            <x-form class="form-horizontal workspace-shipment-form" :route="$asset ? route('shipments.store', $asset) : route('shipments.store-selected')">
                @unless ($asset)
                    @include('partials.forms.edit.asset-select', [
                        'translated_name' => trans('asset-shipments.asset'),
                        'fieldname' => 'asset_id',
                        'select_id' => 'asset_id',
                        'asset_selector_div_id' => 'shipment_asset',
                        'asset_status_type' => 'RTD',
                        'assetInputColumn' => 'col-md-8',
                        'required' => 'true',
                    ])
                @endunless
                @include('asset-shipments.fields', ['shipmentCompanyId' => $asset?->company_id])
                <div class="form-group">
                    <div class="col-md-8 col-md-offset-3">
                        <a href="{{ $asset ? route('hardware.show', $asset) : route('shipments.index') }}" class="btn btn-default">{{ trans('general.cancel') }}</a>
                        <button type="submit" class="btn btn-theme">{{ trans('asset-shipments.save') }}</button>
                    </div>
                </div>
            </x-form>
            @unless ($asset)
                @can('create', \App\Models\Asset::class)
                    <a href="{{ route('hardware.create', ['shipment' => 1]) }}">{{ trans('asset-shipments.create_new_asset') }}</a>
                @endcan
            @endunless
        </x-box>
    </x-container>
@stop
