@extends('layouts/default')

@section('title', trans('asset-shipments.title'))

@section('content')
    <x-container>
        <x-box>
            <div class="workspace-shipment-toolbar">
                <nav class="workspace-shipment-filters" aria-label="{{ trans('asset-shipments.title') }}">
                    <a class="btn {{ request()->boolean('due') ? 'btn-default' : 'btn-theme' }}" href="{{ route('shipments.index', request()->only('asset')) }}">{{ trans('asset-shipments.all') }}</a>
                    <a class="btn {{ request()->boolean('due') ? 'btn-theme' : 'btn-default' }}" href="{{ route('shipments.index', request()->only('asset') + ['due' => 1]) }}">{{ trans('asset-shipments.due_title') }}</a>
                </nav>
                @can('checkout', \App\Models\Asset::class)
                    <a class="btn btn-theme" href="{{ route('shipments.new') }}">
                        <x-icon type="add" /> {{ trans('asset-shipments.create') }}
                    </a>
                @endcan
            </div>
            @include('asset-shipments.table', ['rows' => $shipments])
            {{ $shipments->links() }}
        </x-box>
    </x-container>
@stop
