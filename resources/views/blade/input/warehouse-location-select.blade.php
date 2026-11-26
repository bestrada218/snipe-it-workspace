{{-- Warehouse locations grouped by their configured hierarchy. Only the deepest level
     can be chosen; the upper levels show as disabled headings. --}}
@props([
    'name',
    'tree',
    'selected' => null,
    'placeholder' => null,
])

<select class="form-control" name="{{ $name }}" {{ $attributes }}>
    <option value="">{{ $placeholder ?? trans('asset-returns.choose_location') }}</option>
    @foreach ($tree as $row)
        <option value="{{ $row['location']->id }}" @disabled(! $row['selectable']) @selected($row['selectable'] && (string) $selected === (string) $row['location']->id)>{!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $row['depth']) !!}{{ $row['location']->name }}</option>
    @endforeach
</select>
