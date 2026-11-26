@extends('layouts/default')

@section('title', trans('warehouse-inventory.title'))

@section('header_right')
    @can('reports.view')
        <a class="btn btn-default" href="{{ route('warehouse-inventory.index', array_merge($filters, ['export' => 'csv'])) }}">
            {{ trans('warehouse-inventory.export') }}
        </a>
    @endcan
@stop

@section('content')
    <x-container>
        <x-box :header="trans('warehouse-inventory.filter_title')">
            <form action="{{ route('warehouse-inventory.index') }}" method="GET">
                <div class="row">
                    <div class="form-group col-md-3 col-sm-6">
                        <label for="warehouse-search">{{ trans('warehouse-inventory.search') }}</label>
                        <input class="form-control" id="warehouse-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="120">
                    </div>
                    <div class="form-group col-md-2 col-sm-6">
                        <label for="warehouse-ownership">{{ trans('warehouse-inventory.ownership') }}</label>
                        <select class="form-control" id="warehouse-ownership" name="ownership">
                            @foreach (['all', 'own', 'external'] as $option)
                                <option value="{{ $option }}" @selected($ownership === $option)>{{ trans('warehouse-inventory.ownership_'.$option) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div @class(['form-group col-md-2 col-sm-6', 'hidden' => $ownership === 'external']) id="warehouse-state-field">
                        <label for="warehouse-state">{{ trans('warehouse-inventory.state') }}</label>
                        <select class="form-control" id="warehouse-state" name="state_id" @disabled($ownership === 'external')>
                            <option value="">{{ trans('warehouse-inventory.all_states') }}</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}" @selected((int) ($filters['state_id'] ?? 0) === (int) $state->id)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3 col-sm-6">
                        <label for="warehouse-location">{{ trans('warehouse-inventory.title') }}</label>
                        <select class="form-control" id="warehouse-location" name="location_id">
                            <option value="">{{ trans($ownership === 'external' ? 'warehouse-inventory.all_external' : 'warehouse-inventory.all_locations') }}</option>
                            @foreach ($warehouseOptions as $id => $name)
                                @continue($ownership !== 'all' && in_array((int) $id, $externalIds, true) !== ($ownership === 'external'))
                                @continue(isset($filters['state_id']) && ($stateByLocation[$id] ?? null) !== (int) $filters['state_id'])
                                <option value="{{ $id }}" @selected((int) ($filters['location_id'] ?? 0) === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2 col-sm-6">
                        <label for="warehouse-type">{{ trans('warehouse-inventory.type') }}</label>
                        <select class="form-control" id="warehouse-type" name="type">
                            <option value="">{{ trans('warehouse-inventory.all_types') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>{{ trans('warehouse-inventory.'.$type) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if ($externalIds === [])
                    <p class="help-block @if ($ownership !== 'external') hidden @endif" id="warehouse-external-empty">
                        {{ trans('warehouse-inventory.no_external') }}
                        @can('superuser')
                            <a href="{{ route('settings.general.index') }}">{{ trans('asset-returns.settings_title') }}</a>
                        @endcan
                    </p>
                @endif
                <button class="btn btn-theme" type="submit">{{ trans('warehouse-inventory.apply') }}</button>
                <a class="btn btn-link" href="{{ route('warehouse-inventory.index') }}">{{ trans('warehouse-inventory.clear') }}</a>
            </form>
        </x-box>

        <div class="row">
            <div class="col-md-8">
                <x-box :header="trans('warehouse-inventory.by_location')">
                    @forelse ($byWarehouse->take(10) as $warehouse)
                        <div class="clearfix">
                            <a href="{{ route('warehouse-inventory.index', array_merge($filters, ['location_id' => $warehouse->id])) }}">{{ $warehouse->name }}</a>
                            <strong class="pull-right">{{ number_format($warehouse->units) }}</strong>
                        </div>
                        <div class="progress progress-sm" aria-hidden="true">
                            <div class="progress-bar" style="width: {{ 100 * $warehouse->units / max(1, $byWarehouse->first()->units) }}%; background-color: var(--workspace-accent, #5e3f73)"></div>
                        </div>
                    @empty
                        <p class="text-muted">{{ trans('warehouse-inventory.empty') }}</p>
                    @endforelse
                    @if ($byWarehouse->count() > 10)
                        <p class="text-muted">{{ trans('warehouse-inventory.top_locations') }}</p>
                    @endif
                </x-box>
            </div>
            <div class="col-md-4">
                <x-box :header="trans('warehouse-inventory.by_type')">
                    @foreach ($types as $type)
                        <div class="clearfix">
                            <a href="{{ route('warehouse-inventory.index', array_merge($filters, ['type' => $type])) }}">{{ trans('warehouse-inventory.'.$type) }}</a>
                            <strong class="pull-right">{{ number_format($byType[$type] ?? 0) }}</strong>
                        </div>
                        <div class="progress progress-sm" aria-hidden="true">
                            <div class="progress-bar" style="width: {{ 100 * ($byType[$type] ?? 0) / max(1, $byType->sum()) }}%; background-color: {{ $type === 'asset' ? 'var(--workspace-accent, #5e3f73)' : 'var(--workspace-info, #33639a)' }}"></div>
                        </div>
                    @endforeach
                </x-box>
            </div>
        </div>

        <x-box :header="trans('warehouse-inventory.details')">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">{{ trans('general.asset_tag') }}</th>
                            <th scope="col">{{ trans('general.item') }}</th>
                            <th scope="col">{{ trans('general.serial_number') }}</th>
                            <th scope="col">{{ trans('warehouse-inventory.type') }}</th>
                            <th scope="col">{{ trans('general.location') }}</th>
                            <th scope="col" class="text-right">{{ trans('warehouse-inventory.units') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><strong>{{ $row->tag ?: '—' }}</strong></td>
                                <td><a href="{{ route($row->type === 'asset' ? 'hardware.show' : 'accessories.show', $row->item_id) }}">{{ $row->name }}</a></td>
                                <td>{{ $row->serial ?: '—' }}</td>
                                <td>{{ trans('warehouse-inventory.'.$row->type) }}</td>
                                <td>{{ $row->warehouse }}</td>
                                <td class="text-right">{{ number_format($row->units) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ trans('warehouse-inventory.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        </x-box>
    </x-container>
@stop


@push('js')
    <script nonce="{{ csrf_token() }}">
        (() => {
            const ownership = document.getElementById('warehouse-ownership');
            const state = document.getElementById('warehouse-state');
            const stateField = document.getElementById('warehouse-state-field');
            const empty = document.getElementById('warehouse-external-empty');
            const warehouse = document.getElementById('warehouse-location');
            const warehouses = @js($warehouseOptions->map(fn ($name, $id) => [(string) $id, $name])->values());
            const stateByLocation = @js($stateByLocation);

            const externalIds = new Set(@js($externalIds).map(String));
            const allLocations = @js(trans('warehouse-inventory.all_locations'));
            const allExternal = @js(trans('warehouse-inventory.all_external'));

            function updateWarehouseChoices() {
                const externalOnly = ownership.value === 'external';
                stateField.classList.toggle('hidden', externalOnly);
                state.disabled = externalOnly;
                if (externalOnly) {
                    state.value = '';
                }
                empty?.classList.toggle('hidden', !externalOnly);
                const previous = warehouse.value;
                warehouse.length = 1;
                warehouse.options[0].text = externalOnly ? allExternal : allLocations;
                for (const [id, name] of warehouses) {
                    const external = externalIds.has(id);
                    if ((ownership.value === 'all' || external === externalOnly)
                        && (!state.value || String(stateByLocation[id]) === state.value)) {
                        warehouse.add(new Option(name, id, false, id === previous));
                    }
                }
            }

            ownership.addEventListener('change', updateWarehouseChoices);
            state.addEventListener('change', updateWarehouseChoices);
        })();
    </script>
@endpush
