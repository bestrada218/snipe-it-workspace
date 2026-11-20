@extends('layouts/default')

@section('title0')

  @php
      $requestStatusType = request()->input('status_type');
      $requestOrderNumber = request()->input('order_number');
      $requestCompanyId = request()->input('company_id');
      $requestStatusTypeId = request()->input('status_id');
      $requestPastEol = request()->boolean('past_eol');
  @endphp

  @if ($inventoryView)
    {{ \App\Models\Setting::inventoryLocationModuleLabel($inventoryView) }}
  @else

  @if (is_scalar($requestCompanyId) && ($company instanceof \App\Models\Company))
    {{ $company->name }}
  @endif



  @if ($requestStatusType)
      @if ($requestStatusType=='Pending')
    {{ trans('general.pending') }}
      @elseif ($requestStatusType=='RTD')
    {{ trans('general.ready_to_deploy') }}
      @elseif ($requestStatusType=='Deployed')
    {{ trans('general.deployed') }}
      @elseif ($requestStatusType=='Undeployable')
    {{ trans('general.undeployable') }}
      @elseif ($requestStatusType=='Deployable')
    {{ trans('general.deployed') }}
      @elseif ($requestStatusType=='Requestable')
    {{ trans('admin/hardware/general.requestable') }}
      @elseif ($requestStatusType=='Archived')
    {{ trans('general.archived') }}
      @elseif ($requestStatusType=='Deleted')
    {{ ucfirst(trans('general.deleted')) }}
      @elseif ($requestStatusType=='byod')
    {{ strtoupper(trans('general.byod')) }}
  @endif
@else
{{ trans('general.all') }}
@endif
{{ \App\Models\Setting::inventoryModuleLabel('assets') }}

  @if (Request::has('order_number') && is_scalar($requestOrderNumber))
    : Order #{{ strval($requestOrderNumber) }}
  @endif
  @endif
@stop

@section('header_right')
    @if ($inventoryView && Gate::allows('create', \App\Models\Asset::class) && Gate::allows('checkout', \App\Models\Asset::class))
        <a class="btn btn-primary" href="{{ route('hardware.create', ['inventory_view' => $inventoryViewKey]) }}">
            <x-icon type="plus" /> {{ trans('general.new_asset') }}
        </a>
    @endif
@stop

{{-- Page title --}}
@section('title')
@yield('title0')  @parent
@stop


{{-- Page content --}}
@section('content')
    <x-container>
        @if ($statusFilters !== [])
            <nav class="asset-status-chips" aria-label="{{ trans('navigation.status_filters') }}">
                @foreach ($statusFilters as $filter)
                    <a href="{{ $filter['url'] }}" @class(['asset-status-chip', 'is-active' => $filter['active'], 'tone-'.$filter['tone'] => $filter['tone']]) @if ($filter['active']) aria-current="page" @endif>
                        <span>{{ $filter['label'] }}</span>
                        @if ($filter['count'] !== null)
                            <span class="asset-status-chip-count">{{ $filter['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        @endif
        <x-box name="assets" sr_only_title>
            <x-table.assets
                :route="route('api.assets.index', array(
                    'status_type' => is_scalar($requestStatusType) ? $requestStatusType : null,
                    'order_number' => is_scalar($requestOrderNumber) ? strval($requestOrderNumber) : null,
                    'company_id' => is_scalar($requestCompanyId) ? $requestCompanyId : null,
                    'status_id' => is_scalar($requestStatusTypeId) ? $requestStatusTypeId : null,
                    'past_eol' => $requestPastEol ? 1 : null,
                    'inventory_view' => $inventoryViewKey,
                ))"
                :table_header="$inventoryView ? \App\Models\Setting::inventoryLocationModuleLabel($inventoryView) : \App\Models\Setting::inventoryModuleLabel('assets')"
                :status_type="is_scalar($requestStatusType) ? $requestStatusType : null"
            />
        </x-box>
        <x-shiftclick/>
    </x-container>
@stop

@section('moar_scripts')
@include('partials.bootstrap-table')

@stop
