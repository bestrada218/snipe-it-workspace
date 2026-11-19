@extends('layouts/default')

@section('title', trans('duplicate-assignments.title'))

@section('content')
    <x-container>
        <x-box>
            <p class="text-muted">{{ trans('duplicate-assignments.intro') }}</p>

            @forelse ($alerts as $alert)
                @php
                    $person = $people->get($alert->person_id);
                    $group = $membersByGroup->get($alert->person_id.':'.$alert->category_id, collect());
                @endphp
                @continue(! $person)
                <details class="workspace-duplicate-group">
                    <summary>
                        <span>
                            <strong>{{ $person->display_name }}</strong>
                            <span class="text-muted">{{ $alert->category }}</span>
                        </span>
                        <span class="workspace-duplicate-count">{{ trans_choice('duplicate-assignments.units_count', $alert->units) }}</span>
                        <span class="workspace-duplicate-disclosure">
                            {{ trans('duplicate-assignments.show_items') }}
                            <x-icon type="angle-right" aria-hidden="true" />
                        </span>
                    </summary>

                    <ul class="workspace-duplicate-members">
                        @foreach ($group as $member)
                            @php
                                $key = $member->item_type.':'.$member->item_id;
                                $item = $items->get($key);
                                $events = $approvals->get($member->person_id.':'.$key, collect());
                                $current = $item instanceof \App\Models\Asset && $member->assigned_at
                                    ? $events->first(fn ($event) => $event->getRawOriginal('action_date') === $member->assigned_at)
                                    : null;
                                $recorded = $events->reject(fn ($event) => $event->is($current))
                                    ->filter(fn ($event) => isset((json_decode($event->log_meta ?? '', true) ?? [])['_duplicate_assignment_reason']));
                                $currentMeta = $current ? json_decode($current->log_meta ?? '', true) : null;
                            @endphp
                            @if (! $item)
                                <li class="workspace-duplicate-member">{{ trans('duplicate-assignments.missing_item') }}</li>
                                @continue
                            @endif
                            <li class="workspace-duplicate-member">
                                <div class="workspace-duplicate-item-heading">
                                    <a href="{{ \App\Http\Controllers\DuplicateAssignmentsController::itemUrl($item) }}">
                                        @if ($item->asset_tag)
                                            <strong>{{ $item->asset_tag }}</strong>
                                        @endif
                                        <span>{{ $item->name ?: $item->display_name }}</span>
                                    </a>
                                    @if ($member->units > 1)
                                        <span class="text-muted">{{ trans_choice('duplicate-assignments.units_count', $member->units) }}</span>
                                    @endif
                                </div>

                                @if ($currentMeta['_duplicate_assignment_reason'] ?? false)
                                    @include('duplicate-assignments.approval', ['entry' => $current, 'meta' => $currentMeta])
                                @elseif ($item instanceof \App\Models\Asset || $recorded->isEmpty())
                                    <p class="text-muted workspace-duplicate-missing">{{ trans('duplicate-assignments.missing_approval') }}</p>
                                @endif

                                @if ($recorded->isNotEmpty())
                                    <details class="workspace-duplicate-approvals">
                                        <summary>{{ trans($item instanceof \App\Models\Asset ? 'duplicate-assignments.previous_approvals' : 'duplicate-assignments.recorded_approvals') }}</summary>
                                        @foreach ($recorded as $entry)
                                            <div class="workspace-duplicate-recorded">
                                                <small class="text-muted">{{ Helper::getFormattedDateObject($entry->created_at, 'datetime', false) }}</small>
                                                @include('duplicate-assignments.approval', ['meta' => json_decode($entry->log_meta ?? '', true) ?? []])
                                            </div>
                                        @endforeach
                                    </details>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </details>
            @empty
                <p class="text-muted">{{ trans('duplicate-assignments.empty') }}</p>
            @endforelse

            {{ $alerts->links() }}
        </x-box>
    </x-container>
@stop
