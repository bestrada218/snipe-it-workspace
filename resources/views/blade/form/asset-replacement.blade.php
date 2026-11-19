@props(['item'])

@php
    $registerDate = $item->created_at?->toDateString() ?? today()->toDateString();
    $currentDeadline = $item->replacement_due_on?->toDateString();
@endphp

<fieldset
    id="asset-replacement-fields"
    data-registered="{{ $registerDate }}"
    data-today="{{ today()->toDateString() }}"
    data-locale="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-calculated="{{ trans('asset-replacement.calculated') }}"
    data-remaining="{{ trans('asset-replacement.remaining') }}"
>
    <x-form.legend>{{ trans('asset-replacement.title') }}</x-form.legend>
    <div class="form-group">
        <div class="col-md-8 col-md-offset-3">
            <input type="hidden" name="replacement_required" value="0">
            <label class="inventory-module-option">
                <x-input.checkbox id="replacement-required" name="replacement_required" :checked="old('replacement_required', $currentDeadline !== null)" />
                <span>{{ trans('asset-replacement.required') }}</span>
            </label>
            <x-form.error name="replacement_required" />
        </div>
    </div>
    <div id="replacement-options">
        <x-form.row name="replacement_mode" :label="trans('asset-replacement.mode')">
            <x-slot:input>
                <select class="form-control" id="replacement_mode" name="replacement_mode">
                    <option value="years" @selected(old('replacement_mode', $item->replacement_years ? 'years' : 'date') === 'years')>{{ trans('asset-replacement.years_mode') }}</option>
                    <option value="date" @selected(old('replacement_mode', $item->replacement_years ? 'years' : 'date') === 'date')>{{ trans('asset-replacement.date_mode') }}</option>
                </select>
            </x-slot:input>
        </x-form.row>
        <div data-replacement-mode="years">
            <x-form.row name="replacement_years" :label="trans('asset-replacement.years')" :help_text="trans('asset-replacement.registration_hint', ['date' => $registerDate])">
                <x-slot:input>
                    <input class="form-control" id="replacement_years" type="number" name="replacement_years" min="1" max="100" step="1" value="{{ old('replacement_years', $item->replacement_years) }}">
                </x-slot:input>
            </x-form.row>
        </div>
        <div data-replacement-mode="date">
            <x-form.row name="replacement_due_on" :label="trans('asset-replacement.deadline')">
                <x-slot:input>
                    <input class="form-control" id="replacement_due_on" type="date" name="replacement_due_on" min="{{ $currentDeadline && $currentDeadline <= today()->toDateString() ? $currentDeadline : today()->addDay()->toDateString() }}" value="{{ old('replacement_due_on', $currentDeadline) }}">
                </x-slot:input>
            </x-form.row>
        </div>
        <p class="help-block col-md-8 col-md-offset-3" id="replacement-preview" role="status"></p>
    </div>
</fieldset>

@push('js')
    <script nonce="{{ csrf_token() }}">
        document.addEventListener('DOMContentLoaded', function () {
            const fields = document.getElementById('asset-replacement-fields');
            const enabled = fields.querySelector('#replacement-required');
            const options = fields.querySelector('#replacement-options');
            const mode = fields.querySelector('#replacement_mode');
            const years = fields.querySelector('#replacement_years');
            const date = fields.querySelector('#replacement_due_on');
            const preview = fields.querySelector('#replacement-preview');

            function dateAtUtc(value) {
                return new Date(value + 'T00:00:00Z');
            }

            function shiftMonths(source, count) {
                const first = new Date(source);
                first.setUTCDate(1);
                first.setUTCMonth(first.getUTCMonth() + count);

                const end = new Date(first);
                end.setUTCMonth(end.getUTCMonth() + 1, 0);
                first.setUTCDate(Math.min(source.getUTCDate(), end.getUTCDate()));

                return first;
            }

            function remaining(from, to) {
                const months = (to.getUTCFullYear() - from.getUTCFullYear()) * 12 + to.getUTCMonth() - from.getUTCMonth();
                const fullMonths = shiftMonths(from, months) > to ? months - 1 : months;
                const days = Math.round((to - shiftMonths(from, fullMonths)) / 86400000);
                const pieces = [['year', Math.floor(fullMonths / 12)], ['month', fullMonths % 12], ['day', days]]
                    .filter(([, amount]) => amount > 0)
                    .map(([unit, amount]) => new Intl.NumberFormat(fields.dataset.locale, { style: 'unit', unit, unitDisplay: 'long' }).format(amount));
                return new Intl.ListFormat(fields.dataset.locale).format(pieces);
            }

            function render() {
                const useYears = mode.value === 'years';
                options.hidden = !enabled.checked;
                mode.disabled = !enabled.checked;
                years.disabled = !enabled.checked || !useYears;
                date.disabled = !enabled.checked || useYears;
                fields.querySelector('[data-replacement-mode="years"]').hidden = !useYears;
                fields.querySelector('[data-replacement-mode="date"]').hidden = useYears;
                preview.textContent = '';

                if (!enabled.checked) return;

                let deadline;
                if (useYears && years.valueAsNumber >= 1 && years.valueAsNumber <= 100 && years.validity.valid) {
                    deadline = shiftMonths(dateAtUtc(fields.dataset.registered), years.valueAsNumber * 12);
                    const formatted = new Intl.DateTimeFormat(fields.dataset.locale, { dateStyle: 'long', timeZone: 'UTC' }).format(deadline);
                    preview.textContent = fields.dataset.calculated.replace(':date', formatted);
                } else if (!useYears && date.value) {
                    deadline = dateAtUtc(date.value);
                }

                const current = dateAtUtc(fields.dataset.today);
                if (deadline && deadline > current) {
                    const caption = fields.dataset.remaining.replace(':duration', remaining(current, deadline));
                    preview.textContent = [preview.textContent, caption].filter(Boolean).join(' ');
                }
            }

            fields.addEventListener('change', render);
            fields.addEventListener('input', render);
            render();
        });
    </script>
@endpush
