@extends('layouts/default')

@section('title')
    {{ trans('asset-tag-types.title') }}
    @parent
@stop

@section('content')
    <x-container class="col-md-10 col-md-offset-1 col-lg-8 col-lg-offset-2">
        <x-form route="{{ route('settings.asset_tag_types.save') }}">
            <x-box>
                <x-slot:header>
                    <x-icon type="asset-tags"/> {{ trans('asset-tag-types.title') }}
                </x-slot:header>

                <p>{{ trans('asset-tag-types.intro') }}</p>
                <p class="callout callout-warning tag-types-warning">{{ trans('asset-tag-types.rename_warning') }}</p>
                <x-form.error name="types" />

                <div class="table-responsive">
                    <table class="table tag-types-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ trans('asset-tag-types.code') }}</th>
                                <th scope="col">{{ trans('asset-tag-types.name') }}</th>
                                <th scope="col">{{ trans('asset-tag-types.suffix') }}</th>
                                <th scope="col">{{ trans('asset-tag-types.in_use_column') }}</th>
                                <th scope="col">{{ trans('asset-tag-types.remove') }}</th>
                            </tr>
                        </thead>
                        <tbody data-tag-types>
                            @php($rows = old('types', array_map(fn ($type) => $type + ['original' => $type['code']], $types)))
                            @foreach ($rows as $index => $row)
                                @php($original = $row['original'] ?? null)
                                @php($count = $original ? ($usage[$original] ?? 0) : 0)
                                <tr>
                                    <td>
                                        <input type="hidden" name="types[{{ $index }}][original]" value="{{ $original }}">
                                        {{-- Saved types must keep a code and a name; a new row left
                                             empty is simply skipped on save. --}}
                                        <input type="text" class="form-control tag-types-code" name="types[{{ $index }}][code]" value="{{ $row['code'] ?? '' }}"
                                            maxlength="2" @required($original) aria-label="{{ trans('asset-tag-types.code') }}" placeholder="{{ trans('asset-tag-types.code_placeholder') }}">
                                        <x-form.error name="types.{{ $index }}.code" />
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="types[{{ $index }}][name]" value="{{ $row['name'] ?? '' }}"
                                            maxlength="60" @required($original) aria-label="{{ trans('asset-tag-types.name') }}" placeholder="{{ trans('asset-tag-types.name_placeholder') }}">
                                        <x-form.error name="types.{{ $index }}.name" />
                                    </td>
                                    <td class="text-center">
                                        <input type="hidden" name="types[{{ $index }}][suffix]" value="0">
                                        <input type="checkbox" name="types[{{ $index }}][suffix]" value="1" @checked(filter_var($row['suffix'] ?? false, FILTER_VALIDATE_BOOLEAN))
                                            aria-label="{{ trans('asset-tag-types.suffix') }}">
                                    </td>
                                    <td>{{ trans_choice('asset-tag-types.in_use_tags', $count, ['count' => $count]) }}</td>
                                    <td class="text-center">
                                        @if ($original === null)
                                            <button type="button" class="btn btn-default btn-sm" data-discard-tag-type
                                                aria-label="{{ trans('asset-tag-types.discard') }}" title="{{ trans('asset-tag-types.discard') }}">
                                                <x-icon type="x" />
                                            </button>
                                        @elseif ($count === 0)
                                            <input type="hidden" name="types[{{ $index }}][remove]" value="0">
                                            <input type="checkbox" name="types[{{ $index }}][remove]" value="1" @checked(filter_var($row['remove'] ?? false, FILTER_VALIDATE_BOOLEAN))
                                                aria-label="{{ trans('asset-tag-types.remove') }}">
                                        @else
                                            <span class="text-muted" title="{{ trans('asset-tag-types.in_use', ['code' => $original, 'count' => $count]) }}">—</span>
                                        @endif
                                        <x-form.error name="types.{{ $index }}.remove" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-default" data-add-tag-type>
                    <x-icon type="plus" /> {{ trans('asset-tag-types.add') }}
                </button>
                <p class="help-block">{{ trans('asset-tag-types.suffix_help') }}</p>
            </x-box>
        </x-form>
    </x-container>

    <template id="tag-type-row">
        <tr>
            <td>
                <input type="hidden" data-name="original" value="">
                <input type="text" class="form-control tag-types-code" data-name="code" maxlength="2" aria-label="{{ trans('asset-tag-types.code') }}" placeholder="{{ trans('asset-tag-types.code_placeholder') }}">
            </td>
            <td>
                <input type="text" class="form-control" data-name="name" maxlength="60" aria-label="{{ trans('asset-tag-types.name') }}" placeholder="{{ trans('asset-tag-types.name_placeholder') }}">
            </td>
            <td class="text-center">
                <input type="hidden" data-name="suffix" value="0">
                <input type="checkbox" data-name="suffix" value="1" aria-label="{{ trans('asset-tag-types.suffix') }}">
            </td>
            <td>{{ trans_choice('asset-tag-types.in_use_tags', 0) }}</td>
            <td class="text-center">
                <button type="button" class="btn btn-default btn-sm" data-discard-tag-type
                    aria-label="{{ trans('asset-tag-types.discard') }}" title="{{ trans('asset-tag-types.discard') }}">
                    <x-icon type="x" />
                </button>
            </td>
        </tr>
    </template>
@stop

@push('js')
    <script nonce="{{ csrf_token() }}">
        (function () {
            const body = document.querySelector('[data-tag-types]');
            const template = document.getElementById('tag-type-row');
            let next = body.rows.length;

            document.querySelector('[data-add-tag-type]').addEventListener('click', () => {
                const row = template.content.firstElementChild.cloneNode(true);
                row.querySelectorAll('[data-name]').forEach(input => {
                    input.name = 'types[' + next + '][' + input.dataset.name + ']';
                });
                next++;
                body.appendChild(row);
                row.querySelector('.tag-types-code').focus();
            });

            // A row that was never saved is just taken off the form.
            body.addEventListener('click', event => {
                const discard = event.target.closest('[data-discard-tag-type]');
                if (discard) {
                    discard.closest('tr').remove();
                }
            });

            // Codes are two uppercase letters or digits.
            body.addEventListener('input', event => {
                if (event.target.classList.contains('tag-types-code')) {
                    event.target.value = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                }
            });
        })();
    </script>
@endpush
