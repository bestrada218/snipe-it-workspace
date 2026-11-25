@extends('layouts/default')

@section('title', trans('general.help_center'))

@section('content')
    <x-container>
        <div class="help-home">
            <p class="help-home-intro">{{ trans('general.help_center_intro') }}</p>
            <label class="help-search">
                <span class="sr-only">{{ trans('general.help_search') }}</span>
                <x-icon type="search" />
                <input class="form-control" type="search" placeholder="{{ trans('general.help_search_placeholder') }}" data-help-search autocomplete="off">
            </label>

            <ol class="help-cards">
                @foreach ($sections as $number => $section)
                    <li class="help-card" data-help-card data-help-text="{{ mb_strtolower($section['title'].' '.strip_tags($section['html'])) }}">
                        <h2><a href="{{ route('help.show', $section['slug']) }}"><span class="help-card-number">{{ $number + 1 }}</span> {{ $section['title'] }}</a></h2>
                        <p>{{ $section['intro'] }}</p>
                        <ul>
                            @foreach (collect($section['toc'])->where('level', 2) as $heading)
                                <li data-help-topic><a href="{{ route('help.show', $section['slug']) }}#{{ $heading['id'] }}">{{ $heading['text'] }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
            <p class="help-no-results" data-help-empty hidden>{{ trans('general.help_no_results') }}</p>
        </div>
    </x-container>
@stop

@push('js')
    <script nonce="{{ csrf_token() }}">
        (() => {
            const input = document.querySelector('[data-help-search]');
            const cards = [...document.querySelectorAll('[data-help-card]')];
            const empty = document.querySelector('[data-help-empty]');
            const normalize = text => text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
            input.addEventListener('input', () => {
                const words = normalize(input.value).split(/\s+/).filter(Boolean);
                let shown = 0;
                cards.forEach(card => {
                    const text = normalize(card.dataset.helpText);
                    const match = words.every(word => text.includes(word));
                    card.hidden = ! match;
                    shown += match ? 1 : 0;
                });
                empty.hidden = shown > 0;
            });
        })();
    </script>
@endpush
