@extends('layouts/default')

@section('title', trans('asset-replacement.alerts'))

@section('content')
    @php($today = \Carbon\CarbonImmutable::today())

    <x-container>
        <x-box>
            <form method="GET" class="form-inline" action="{{ route('hardware.replacement-alerts') }}">
                <label for="window">{{ trans('asset-replacement.filter') }}</label>
                <select name="window" id="window" class="form-control">
                    <option value="all">{{ trans('asset-replacement.all_scheduled') }}</option>
                    @foreach (array_keys($windows) as $window)
                        <option value="{{ $window }}" @selected($selected === $window)>{{ trans('asset-replacement.windows.'.$window) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-theme">{{ trans('general.search') }}</button>
            </form>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">{{ trans('admin/hardware/table.asset_tag') }}</th>
                            <th scope="col">{{ trans('general.name') }}</th>
                            <th scope="col">{{ trans('general.location') }}</th>
                            <th scope="col">{{ trans('asset-replacement.deadline') }}</th>
                            <th scope="col" class="text-right">{{ trans('asset-replacement.days_remaining') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assets as $asset)
                            @php($daysRemaining = (int) round($today->diffInDays($asset->replacement_due_on, false)))
                            <tr>
                                <td><a href="{{ route('hardware.show', $asset) }}">{{ $asset->asset_tag }}</a></td>
                                <td>{{ $asset->display_name }}</td>
                                <td>{{ $asset->location?->name }}</td>
                                <td>{{ Helper::getFormattedDateObject($asset->replacement_due_on, 'date', false) }}</td>
                                <td class="text-right">
                                    @if ($daysRemaining < 0)
                                        {{ trans_choice('asset-replacement.days_overdue', abs($daysRemaining)) }}
                                    @elseif ($daysRemaining === 0)
                                        {{ trans('asset-replacement.due_today') }}
                                    @else
                                        {{ trans_choice('asset-replacement.days_until_due', $daysRemaining) }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">{{ trans('asset-replacement.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $assets->links() }}
        </x-box>
    </x-container>
@stop
