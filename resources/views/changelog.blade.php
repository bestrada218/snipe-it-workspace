@extends('layouts/default')

@section('title', trans('general.workspace_changelog'))

@section('content')
    <x-container>
        <x-box :header="trans('general.workspace_changelog_current', ['version' => config('workspace.version'), 'upstream' => config('version.app_version')])">
            @if ($html)
                <div class="workspace-changelog">
                    {!! $html !!}
                </div>
            @else
                <p>{{ trans('general.workspace_changelog_missing') }}</p>
            @endif
        </x-box>
    </x-container>
@stop
