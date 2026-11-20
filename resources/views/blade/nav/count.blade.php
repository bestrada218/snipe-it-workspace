@props(['value' => null, 'tone' => null])

{{-- Small count beside a side-menu entry; amber when it needs attention. --}}
@if (filled($value))
    <span class="pull-right-container">
        <small @class(['label', 'pull-right', 'nav-count', 'nav-count-warn' => $tone === 'warn'])>{{ $value }}</small>
    </span>
@endif
