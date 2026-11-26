@component('mail::message')
# {{ trans('mail-settings.digest.title') }}

{{ trans('mail-settings.digest.intro', ['date' => now()->toDateString()]) }}

@if ($sections === [])
{{ trans('mail-settings.digest.nothing') }}

@endif
@foreach ($sections as $key => $section)
- {{ trans('mail-settings.digest.sections.'.$key) }} ({{ $section['count'] }})
@endforeach

@component('mail::button', ['url' => route('home')])
{{ trans('mail-settings.digest.open') }}
@endcomponent

{{ trans('mail-settings.digest.footer') }}
@endcomponent
