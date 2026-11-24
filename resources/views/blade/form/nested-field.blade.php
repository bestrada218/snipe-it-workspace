{{-- A labelled input whose name is a dotted path (s3.key → s3[key]), for
     forms that post nested arrays. Secrets pass :value="null". --}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'placeholder' => null,
    'autocomplete' => 'off',
])

@php
    $parts = explode('.', $name);
    $inputName = array_shift($parts).implode('', array_map(fn ($part) => "[{$part}]", $parts));
    $id = 'field-'.str_replace('.', '-', $name);
@endphp

<div @class(['form-group', 'has-error' => $errors->has($name)])>
    <label for="{{ $id }}" class="col-md-3 control-label">{{ $label }}</label>
    <div class="col-md-7">
        @if ($type === 'textarea')
            <textarea class="form-control" id="{{ $id }}" name="{{ $inputName }}" rows="4" placeholder="{{ $placeholder }}" {{ $attributes }}>{{ $value }}</textarea>
        @else
            <input class="form-control" id="{{ $id }}" name="{{ $inputName }}" type="{{ $type }}" value="{{ $value }}" placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}" {{ $attributes }}>
        @endif
        @if ($help)
            <p class="help-block">{{ $help }}</p>
        @endif
        <x-form.error :name="$name" />
    </div>
</div>
