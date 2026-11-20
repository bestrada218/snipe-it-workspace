@php
    $approval = $meta['_duplicate_assignment_approval'] ?? [];
    $evidencePath = \App\Services\DuplicateAssignments::evidencePath($approval);
@endphp
<dl class="workspace-duplicate-approval">
    <div>
        <dt>{{ trans('duplicate-assignments.reason') }}</dt>
        <dd>{{ $meta['_duplicate_assignment_reason'] ?? '' }}</dd>
    </div>
    <div>
        <dt>{{ trans('duplicate-assignments.ticket') }}</dt>
        <dd>{{ $meta['_duplicate_assignment_ticket'] ?? trans('activity-log.target_not_recorded') }}</dd>
    </div>
    @if ($approval === [])
    <div>
        <dt>{{ trans('duplicate-assignments.approved_by') }}</dt>
        <dd class="text-muted">{{ trans('duplicate-assignments.justification_only') }}</dd>
    </div>
    @else
    <div>
        <dt>{{ trans('duplicate-assignments.approved_by') }}</dt>
        <dd>{{ $approval['approver_name'] ?? trans('activity-log.target_not_recorded') }}</dd>
    </div>
    <div>
        <dt>{{ trans('duplicate-assignments.evidence') }}</dt>
        <dd>
            @if ($evidencePath && \Illuminate\Support\Facades\Storage::exists($evidencePath))
                <a href="{{ route('duplicate-assignments.evidence', $entry->id) }}">{{ $approval['filename'] }}</a>
            @elseif ($evidencePath)
                {{ trans('activity-log.file_unavailable') }}
            @else
                {{ trans('duplicate-assignments.no_evidence') }}
            @endif
        </dd>
    </div>
    @endif
</dl>
