@extends('layouts/default')

@section('title')
    {{ trans('navigation.search_results') }}
    @parent
@stop

@section('content')
    <x-container>
        <form method="GET" action="{{ route('search') }}" class="search-page-form" role="search">
            <label for="search-page-q" class="sr-only">{{ trans('navigation.search_label') }}</label>
            <input id="search-page-q" name="q" type="search" class="form-control" value="{{ $term }}"
                placeholder="{{ trans('navigation.search_placeholder') }}" autocomplete="off" autofocus>
            <button type="submit" class="btn btn-primary"><x-icon type="search" /> {{ trans('general.search') }}</button>
        </form>

        @if ($term === '')
            <p class="text-muted">{{ trans('navigation.search_prompt') }}</p>
        @elseif ($tooShort)
            <p class="text-muted">{{ trans('navigation.search_too_short') }}</p>
        @elseif ($assets->isEmpty() && $items->isEmpty() && $people->isEmpty())
            <p class="text-muted">{{ trans('navigation.search_no_results', ['term' => $term]) }}</p>
        @endif

        @if ($assets->isNotEmpty())
            <x-box :header="trans('navigation.results_assets').' ('.$assets->count().')'">
                <div class="table-responsive">
                    <table class="table search-results">
                        <thead>
                            <tr>
                                <th>{{ trans('admin/hardware/form.tag') }}</th>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ trans('admin/hardware/table.serial') }}</th>
                                <th>{{ trans('general.status') }}</th>
                                <th>{{ trans('admin/hardware/form.checkedout_to') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assets as $asset)
                                <tr>
                                    <td><a class="search-tag" href="{{ route('hardware.show', $asset) }}">{{ $asset->asset_tag }}</a></td>
                                    <td>
                                        {{ $asset->name ?: $asset->model?->name }}
                                        @if ($asset->name && $asset->model)
                                            <div class="text-muted small">{{ $asset->model->name }}</div>
                                        @endif
                                    </td>
                                    <td class="search-mono">{{ $asset->serial }}</td>
                                    <td>{{ $asset->status?->name }}</td>
                                    <td>{{ $asset->assignedTo?->display_name ?? $asset->assignedTo?->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($assets->count() >= 25)
                    <p class="text-muted small">{{ trans('navigation.results_limited') }}</p>
                @endif
            </x-box>
        @endif

        @if ($items->isNotEmpty())
            <x-box :header="trans('navigation.results_items').' ('.$items->count().')'">
                <div class="table-responsive">
                    <table class="table search-results">
                        <thead>
                            <tr>
                                <th>{{ trans('admin/hardware/form.tag') }}</th>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ trans('general.type') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>
                                        <a class="search-tag" href="{{ route($item['route'], $item['model']) }}">{{ $item['model']->asset_tag ?: trans('navigation.no_tag') }}</a>
                                    </td>
                                    <td>{{ $item['model']->name }}</td>
                                    <td>{{ \App\Models\Setting::inventoryModuleLabel($item['module']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-box>
        @endif

        @if ($people->isNotEmpty())
            <x-box :header="trans('navigation.results_people').' ('.$people->count().')'">
                <div class="table-responsive">
                    <table class="table search-results">
                        <thead>
                            <tr>
                                <th>{{ trans('general.name') }}</th>
                                <th>{{ trans('admin/users/table.username') }}</th>
                                <th>{{ trans('general.email') }}</th>
                                <th>{{ trans('general.location') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($people as $person)
                                <tr>
                                    <td><a href="{{ route('users.show', $person) }}">{{ $person->display_name }}</a></td>
                                    <td>{{ $person->username }}</td>
                                    <td>{{ $person->email }}</td>
                                    <td>{{ $person->location?->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-box>
        @endif
    </x-container>
@stop
