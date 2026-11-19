@props(['name', 'keyPath', 'label', 'stored', 'placeholder'])

@php
    $editLabel = trans('admin/settings/general.inventory_display_name', ['module' => $label]);
@endphp

<details class="inventory-module-editor" @if ($errors->has($keyPath) || old($keyPath, $stored) !== $stored) open @endif>
    <summary aria-label="{{ trans('button.var.edit', ['item_type' => $label]) }}" title="{{ $editLabel }}">
        <x-icon type="edit" />
    </summary>
    <input
        type="text"
        class="form-control"
        name="{{ $name }}"
        value="{{ old($keyPath, $stored) }}"
        maxlength="60"
        placeholder="{{ $placeholder }}"
        aria-label="{{ $editLabel }}"
    >
    <x-form.error :name="$keyPath" />
</details>
