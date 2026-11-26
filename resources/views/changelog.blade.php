@extends('layouts/default')

@section('title', trans('general.workspace_changelog', [], $notesLocale))

@section('content')
    <x-container>
        <x-box :header="trans('general.workspace_changelog_current', ['version' => config('workspace.version'), 'upstream' => config('version.app_version')], $notesLocale)">
            @if ($releases)
                <div class="workspace-changelog">
                    @foreach ($releases as $release)
                        <article class="workspace-changelog-release">
                            <h2>{{ $release['title'] }}</h2>
                            {!! $release['intro'] !!}
                            @if ($release['topics'])
                                <div class="workspace-changelog-topics">
                                    @foreach ($release['topics'] as $topic)
                                        <section class="workspace-changelog-topic">
                                            {!! $topic !!}
                                        </section>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <p>{{ trans('general.workspace_changelog_missing', [], $notesLocale) }}</p>
            @endif
        </x-box>
    </x-container>
@stop
