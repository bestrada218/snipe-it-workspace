@props(['current' => null])

@php($damagedIds = collect(\App\Services\DamageReports::statusIds()))

{{-- Required documentation when the chosen status blocks assigning the asset
     (for example "Damage"). Shown only when the status changes into one. --}}
<fieldset class="damage-report" data-damage-report data-damaged-ids="{{ $damagedIds->toJson() }}" data-current="{{ $current }}"
    @if (! $errors->hasAny(['damage_description', 'damage_repairable', 'damage_repair_attempted', 'damage_repair_details'])) hidden @endif>
    <div class="col-md-8 col-md-offset-3">
        <legend>{{ trans('damage-reports.title') }}</legend>
        <p class="help-block">{{ trans('damage-reports.intro') }}</p>

        <label for="damage_description">{{ trans('damage-reports.description') }} <span class="text-danger">*</span></label>
        <textarea class="form-control" id="damage_description" name="damage_description" rows="3" maxlength="2000"
            placeholder="{{ trans('damage-reports.description_placeholder') }}">{{ old('damage_description') }}</textarea>
        <x-form.error name="damage_description" />

        <span class="damage-report-label">{{ trans('damage-reports.repairable') }} <span class="text-danger">*</span></span>
        <div class="damage-report-options" role="radiogroup">
            @foreach (\App\Services\DamageReports::REPAIRABLE as $option)
                <label><input type="radio" name="damage_repairable" value="{{ $option }}" @checked(old('damage_repairable') === $option)> {{ trans('damage-reports.repairable_options.'.$option) }}</label>
            @endforeach
        </div>
        <x-form.error name="damage_repairable" />

        <span class="damage-report-label">{{ trans('damage-reports.repair_attempted') }} <span class="text-danger">*</span></span>
        <div class="damage-report-options" role="radiogroup">
            <label><input type="radio" name="damage_repair_attempted" value="1" @checked(old('damage_repair_attempted') === '1')> {{ trans('damage-reports.yes') }}</label>
            <label><input type="radio" name="damage_repair_attempted" value="0" @checked(old('damage_repair_attempted') === '0')> {{ trans('damage-reports.no') }}</label>
        </div>
        <x-form.error name="damage_repair_attempted" />

        <div data-damage-repair-details @if (old('damage_repair_attempted') !== '1') hidden @endif>
            <label for="damage_repair_details">{{ trans('damage-reports.repair_details') }} <span class="text-danger">*</span></label>
            <textarea class="form-control" id="damage_repair_details" name="damage_repair_details" rows="2" maxlength="2000"
                placeholder="{{ trans('damage-reports.repair_details_placeholder') }}">{{ old('damage_repair_details') }}</textarea>
            <x-form.error name="damage_repair_details" />
        </div>
    </div>
</fieldset>

@once
    @push('js')
        <script nonce="{{ csrf_token() }}">
            $(function () {
                document.querySelectorAll('[data-damage-report]').forEach(report => {
                    const form = report.closest('form');
                    const damagedIds = JSON.parse(report.dataset.damagedIds || '[]');
                    const current = Number(report.dataset.current || 0);
                    const details = report.querySelector('[data-damage-repair-details]');
                    const sync = () => {
                        const selected = Number($(form).find('[name="status_id"]').val() || 0);
                        report.hidden = !(damagedIds.includes(selected) && selected !== current);
                    };
                    $(form).on('change', '[name="status_id"]', sync);
                    report.addEventListener('change', event => {
                        if (event.target.name === 'damage_repair_attempted') {
                            details.hidden = event.target.value !== '1';
                        }
                    });
                    sync();
                });
            });
        </script>
    @endpush
@endonce
