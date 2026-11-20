@props(['label'])

{{-- A labelled section of the side menu. The header only renders when at
     least one entry survives the permission checks inside the slot. --}}
@if (trim((string) $slot) !== '')
    <li class="header nav-section">{{ $label }}</li>
    {{ $slot }}
@endif
