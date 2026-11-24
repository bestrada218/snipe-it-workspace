@extends('layouts/default')

@section('title', trans('asset-shipments.title'))

@section('content')
    <x-container>
        <x-box>
            <div class="workspace-shipment-toolbar">
                <nav class="workspace-shipment-filters" aria-label="{{ trans('asset-shipments.title') }}">
                    @foreach (['current' => [], 'due' => ['due' => 1], 'past' => ['view' => 'past']] as $tab => $query)
                        <a class="btn {{ $view === $tab ? 'btn-theme' : 'btn-default' }}" href="{{ route('shipments.index', request()->only('asset') + $query) }}"
                            @if ($view === $tab) aria-current="page" @endif>{{ trans('asset-shipments.tab_'.$tab) }}</a>
                    @endforeach
                </nav>
                @can('checkout', \App\Models\Asset::class)
                    <a class="btn btn-theme" href="{{ route('shipments.new') }}">
                        <x-icon type="add" /> {{ trans('asset-shipments.create') }}
                    </a>
                @endcan
            </div>
            @include('asset-shipments.table', ['rows' => $shipments, 'past' => $view === 'past', 'empty' => trans('asset-shipments.empty_'.$view)])
            {{ $shipments->links() }}
        </x-box>
    </x-container>
@stop
