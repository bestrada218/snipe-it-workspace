@php
    $shortcuts = [
        [\App\Models\Asset::class, 'hardware.create', 'new_asset'],
        [\App\Models\License::class, 'licenses.create', 'new_license'],
        [\App\Models\Accessory::class, 'accessories.create', 'new_accessory'],
        [\App\Models\Consumable::class, 'consumables.create', 'new_consumable'],
        [\App\Models\Component::class, 'components.create', 'new_component'],
        [\App\Models\User::class, 'users.create', 'new_user'],
    ];
@endphp

<section class="box workspace-empty" aria-labelledby="workspace-empty-title">
    <div class="box-body">
        <h2 id="workspace-empty-title">{{ trans('general.dashboard_empty_title') }}</h2>
        <p>{{ trans('general.dashboard_empty_description') }}</p>
        <div class="workspace-empty-actions">
            @foreach ($shortcuts as [$model, $route, $label])
                @can('create', $model)
                    <a class="btn {{ $loop->first ? 'btn-primary' : 'workspace-secondary-action' }}" href="{{ route($route) }}">
                        @if ($loop->first)
                            <x-icon type="plus" />
                        @endif
                        {{ trans('general.'.$label) }}
                    </a>
                @endcan
            @endforeach
        </div>
    </div>
</section>
