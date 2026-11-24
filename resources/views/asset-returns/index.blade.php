@extends('layouts/default')

@section('title', trans('asset-returns.title'))

@section('content')
    <x-container>
        <x-box>
            <div class="workspace-shipment-toolbar">
                <nav class="workspace-shipment-filters" aria-label="{{ trans('asset-returns.title') }}">
                    @foreach (['open' => [], 'closed' => ['view' => 'closed']] as $tab => $query)
                        <a class="btn {{ $view === $tab ? 'btn-theme' : 'btn-default' }}" href="{{ route('returns.index', $query) }}"
                            @if ($view === $tab) aria-current="page" @endif>{{ trans('asset-returns.tab_'.$tab) }}</a>
                    @endforeach
                </nav>
                @can('checkin', \App\Models\Asset::class)
                    <a class="btn btn-theme" href="{{ route('returns.create') }}"><x-icon type="add" /> {{ trans('asset-returns.create') }}</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ trans('asset-returns.person') }}</th>
                            <th scope="col">{{ trans('asset-returns.reason') }}</th>
                            <th scope="col">{{ trans('asset-returns.ticket') }}</th>
                            <th scope="col">{{ trans('asset-returns.return_owner') }}</th>
                            <th scope="col">{{ trans('asset-returns.progress') }}</th>
                            <th scope="col">{{ $view === 'closed' ? trans('asset-returns.closed_on') : trans('asset-returns.created_on') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $return)
                            @php($done = $return->items->filter->isDone()->count())
                            <tr>
                                <td><a href="{{ route('returns.show', $return) }}"><strong>#{{ $return->id }}</strong></a></td>
                                <td><a href="{{ route('returns.show', $return) }}">{{ $return->person?->display_name }}</a></td>
                                <td>{{ trans('asset-returns.reasons.'.$return->reason) }}</td>
                                <td>{{ $return->ticket_number }}</td>
                                <td>{{ $return->returnOwner?->display_name }}</td>
                                <td>
                                    @if ($return->status() === 'open')
                                        {{ $done }} / {{ trans_choice('asset-returns.items_count', $return->items_count, ['count' => $return->items_count]) }}
                                    @else
                                        <span class="workspace-shipment-status" data-status="{{ $return->status() === 'completed' ? 'received' : 'cancelled' }}">{{ trans('asset-returns.statuses.'.$return->status()) }}</span>
                                    @endif
                                </td>
                                <td>{{ Helper::getFormattedDateObject($view === 'closed' ? ($return->completed_at ?? $return->cancelled_at) : $return->created_at, 'date', false) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">{{ trans('asset-returns.empty_'.$view) }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $returns->links() }}
        </x-box>
    </x-container>
@stop
