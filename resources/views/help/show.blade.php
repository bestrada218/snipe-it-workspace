@extends('layouts/default')

@section('title', $section['title'])

@section('content')
    <x-container>
        <div class="help-layout">
            <nav class="help-nav" aria-label="{{ trans('general.help_contents') }}">
                @if (count($section['toc']))
                    <h2>{{ trans('general.help_on_this_page') }}</h2>
                    <ul class="help-toc">
                        @foreach ($section['toc'] as $heading)
                            <li @class(['help-toc-sub' => $heading['level'] === 3])><a href="#{{ $heading['id'] }}">{{ $heading['text'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
                <h2>{{ trans('general.help_all_sections') }}</h2>
                <ol class="help-sections">
                    @foreach ($sections as $item)
                        <li @class(['is-current' => $item['slug'] === $section['slug']])>
                            <a href="{{ route('help.show', $item['slug']) }}" @if ($item['slug'] === $section['slug']) aria-current="page" @endif>{{ $item['title'] }}</a>
                        </li>
                    @endforeach
                </ol>
                <a class="help-nav-home" href="{{ route('help.index') }}"><x-icon type="help" /> {{ trans('general.help_center') }}</a>
            </nav>

            <article class="help-article box">
                <div class="box-body">
                    {!! $section['html'] !!}

                    <nav class="help-pager" aria-label="{{ trans('general.help_all_sections') }}">
                        @if ($previous)
                            <a href="{{ route('help.show', $previous['slug']) }}" class="help-pager-previous">
                                <span>{{ trans('general.help_previous') }}</span>
                                {{ $previous['title'] }}
                            </a>
                        @endif
                        @if ($next)
                            <a href="{{ route('help.show', $next['slug']) }}" class="help-pager-next">
                                <span>{{ trans('general.help_next') }}</span>
                                {{ $next['title'] }}
                            </a>
                        @endif
                    </nav>
                </div>
            </article>
        </div>
    </x-container>
@stop
