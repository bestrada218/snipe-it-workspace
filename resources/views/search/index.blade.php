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
        @elseif ($nothingFound)
            <p class="text-muted">{{ trans('navigation.search_no_results', ['term' => $term]) }}</p>
        @endif

        @if ($assets && $assets['rows']->isNotEmpty())
            <x-box :header="trans('navigation.results_assets')">
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
                            @foreach ($assets['rows'] as $asset)
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
                @if ($assets['more'])
                    <a class="search-more" href="{{ route('search', ['q' => $term, 'more' => 'assets', 'all' => 1]) }}">{{ trans('navigation.show_more') }}</a>
                @endif
            </x-box>
        @endif

        @foreach ($items as $module => $result)
            <x-box :header="\App\Models\Setting::inventoryModuleLabel($module)">
                <div class="table-responsive">
                    <table class="table search-results">
                        <thead>
                            <tr>
                                <th>{{ trans('admin/hardware/form.tag') }}</th>
                                <th>{{ trans('general.name') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result['rows'] as $model)
                                <tr>
                                    <td>
                                        <a class="search-tag" href="{{ route($result['route'], $model) }}">{{ $model->asset_tag ?: trans('navigation.no_tag') }}</a>
                                    </td>
                                    <td>{{ $model->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($result['more'])
                    <a class="search-more" href="{{ route('search', ['q' => $term, 'more' => $module, 'all' => 1]) }}">{{ trans('navigation.show_more') }}</a>
                @endif
            </x-box>
        @endforeach

        @if ($people && $people['rows']->isNotEmpty())
            <x-box :header="trans('navigation.results_people')">
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
                            @foreach ($people['rows'] as $person)
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
                @if ($people['more'])
                    <a class="search-more" href="{{ route('search', ['q' => $term, 'more' => 'people']) }}">{{ trans('navigation.show_more') }}</a>
                @endif
            </x-box>
        @endif
    </x-container>
@stop
