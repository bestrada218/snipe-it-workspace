@props(['id' => 'asset_tag-help', 'target' => 'asset_tag'])

@php($nextSequence = \App\Services\AssetTagSequence::next())

<div class="help-block" id="{{ $id }}">
    <p>{{ \App\Services\AssetTagSequence::hint() }}</p>
    <p>
        <code>XX-KKK-PP[-TTT]</code>
        <span>{{ trans('asset-tags.quick_structure') }}</span>
        <a href="{{ route('guide.asset-tags') }}" target="_blank" rel="noopener">{{ trans('asset-tags.full_guide') }}</a>
    </p>

    @if ($nextSequence !== null)
        {{-- Builds XX-KKK-PP[-TTT] into the tag field with the next free sequence. --}}
        <fieldset class="tag-builder" data-tag-builder data-target="{{ $target }}" data-sequence="{{ $nextSequence }}">
            <legend>{{ trans('asset-tags.builder_title') }}</legend>
            <div class="tag-builder-parts">
                <label>
                    <span>{{ trans('asset-tags.builder_prefix') }}</span>
                    <input type="text" class="form-control" data-part="prefix" maxlength="2" list="{{ $id }}-prefixes" autocomplete="off">
                    <datalist id="{{ $id }}-prefixes">
                        @foreach (\App\Services\AssetTagSequence::knownPrefixes() as $prefix)
                            <option value="{{ $prefix }}"></option>
                        @endforeach
                    </datalist>
                </label>
                <label>
                    <span>{{ trans('asset-tags.builder_sequence') }}</span>
                    <input type="text" class="form-control tag-builder-sequence" value="{{ $nextSequence }}" readonly tabindex="-1">
                </label>
                <label>
                    <span>{{ trans('asset-tags.builder_type') }}</span>
                    <select class="form-control" data-part="type">
                        <option value="">{{ trans('asset-tags.builder_choose_type') }}</option>
                        @foreach (\App\Services\AssetTagSequence::TYPE_CODES as $code)
                            <option value="{{ $code }}">{{ $code }} · {{ trans('asset-tags.types.'.$code) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>{{ trans('asset-tags.builder_suffix') }}</span>
                    <select class="form-control" data-part="suffix">
                        <option value="">{{ trans('asset-tags.builder_none') }}</option>
                        @foreach (\App\Services\AssetTagSequence::SUFFIXES as $suffix)
                            <option value="{{ $suffix }}">{{ $suffix }} · {{ trans('asset-tags.suffixes.'.$suffix) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <p class="tag-builder-help">{{ trans('asset-tags.builder_help') }}</p>
        </fieldset>
    @endif
</div>

@once
    @push('js')
        <script nonce="{{ csrf_token() }}">
            document.querySelectorAll('[data-tag-builder]').forEach(builder => {
                const field = document.getElementById(builder.dataset.target);
                if (!field) {
                    return;
                }
                const part = name => builder.querySelector('[data-part="' + name + '"]');
                let lastBuilt = null;
                const build = () => {
                    const prefix = part('prefix').value.trim().toUpperCase();
                    const type = part('type').value;
                    const suffix = part('suffix').value;
                    part('prefix').value = prefix;
                    if (!/^[A-Z0-9]{2}$/.test(prefix) || !type) {
                        return;
                    }
                    // Never overwrite a tag the person typed by hand.
                    if (field.value.trim() !== '' && field.value !== lastBuilt && field.defaultValue !== field.value) {
                        return;
                    }
                    field.value = [prefix, builder.dataset.sequence, type].concat(suffix ? [suffix] : []).join('-');
                    lastBuilt = field.value;
                    field.dispatchEvent(new Event('input', {bubbles: true}));
                };
                builder.addEventListener('input', build);
                builder.addEventListener('change', build);
            });
        </script>
    @endpush
@endonce
