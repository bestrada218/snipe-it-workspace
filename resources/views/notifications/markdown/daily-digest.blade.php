@component('mail::message')
# {{ trans('mail-settings.digest.title') }}

{{ trans('mail-settings.digest.intro', ['date' => now()->toDateString()]) }}

@if ($sections === [])
{{ trans('mail-settings.digest.nothing') }}

@endif
@foreach ($sections as $key => $section)
## {{ trans('mail-settings.digest.sections.'.$key) }} ({{ $section['count'] }})

@foreach ($section['items'] as $item)
- [{{ $item['label'] }}]({{ $item['url'] }}){{ $item['detail'] ? ' — '.$item['detail'] : '' }}
@endforeach
@if ($section['count'] > count($section['items']))
- {{ trans('mail-settings.digest.and_more', ['count' => $section['count'] - count($section['items'])]) }}
@endif

@endforeach
@component('mail::button', ['url' => route('home')])
{{ trans('mail-settings.digest.open') }}
@endcomponent

{{ trans('mail-settings.digest.footer') }}
@endcomponent
