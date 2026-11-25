@extends('layouts/default')

@section('title', trans('mail-settings.title'))

@php
    $value = fn (string $key, $default = null) => old($key, $saved[$key] ?? $default);
    $provider = $value('provider', 'google');
    $sensitive = request()->attributes->get(\App\Http\Middleware\RequireSensitiveConfirmation::FIELD);
    $when = fn (?string $date) => $date ? Helper::getFormattedDateObject($date, 'datetime', false) : '';
@endphp

@section('content')
    <x-container>
        <p class="help-home-intro">{{ trans('mail-settings.intro') }}</p>

        @if ($unreadable)
            <p class="callout callout-danger">{{ trans('mail-settings.unreadable') }}</p>
        @endif

        <form method="POST" action="{{ route('settings.mail.save') }}" class="form-horizontal" autocomplete="off" data-mail-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="_sensitive" value="{{ $sensitive }}">

            <x-box :header="trans('mail-settings.server_title')">
                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        <input type="hidden" name="enabled" value="0">
                        <label class="form-control"><input type="checkbox" name="enabled" value="1" @checked($value('enabled'))> {{ trans('mail-settings.enabled') }}</label>
                        <p class="help-block">{{ trans('mail-settings.enabled_help') }}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="field-provider" class="col-md-3 control-label">{{ trans('mail-settings.provider') }}</label>
                    <div class="col-md-7">
                        <select class="form-control" id="field-provider" name="provider" data-mail-provider>
                            @foreach (\App\Services\Mail\MailSettings::PROVIDERS as $key => $preset)
                                <option value="{{ $key }}" data-preset='@json($preset)' @selected($provider === $key)>{{ trans('mail-settings.providers.'.$key) }}</option>
                            @endforeach
                        </select>
                        <p class="help-block">{{ trans('mail-settings.provider_help') }}</p>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        @foreach (array_keys(\App\Services\Mail\MailSettings::PROVIDERS) as $key)
                            <div class="cloud-guide-box" data-mail-guide="{{ $key }}" @if ($provider !== $key) hidden @endif>
                                <details class="cloud-guide" @unless ($value('enabled')) open @endunless>
                                    <summary>{{ trans('mail-settings.guide') }} · {{ trans('mail-settings.providers.'.$key) }}</summary>
                                    <ol>
                                        @foreach (trans('mail-settings.guides.'.$key) as $step)
                                            <li>{{ $step }}</li>
                                        @endforeach
                                    </ol>
                                </details>
                            </div>
                        @endforeach
                    </div>
                </div>

                <x-form.nested-field name="host" :label="trans('mail-settings.host')" :value="$value('host', 'smtp.gmail.com')" :help="trans('mail-settings.host_help')" data-mail-field="host" />
                <x-form.nested-field name="port" type="number" :label="trans('mail-settings.port')" :value="$value('port', 587)" :help="trans('mail-settings.port_help')" data-mail-field="port" min="1" max="65535" />
                <x-form.nested-field name="username" :label="trans('mail-settings.username')" :value="$value('username')" :help="trans('mail-settings.username_help')" data-mail-field="username" />
                <x-form.nested-field name="password" type="password" :label="trans('mail-settings.password')" :placeholder="$hasPassword ? trans('mail-settings.saved_secret') : null" :help="trans('mail-settings.password_help')" autocomplete="new-password" />
                <x-form.nested-field name="from_address" type="email" :label="trans('mail-settings.from_address')" :value="$value('from_address')" :help="trans('mail-settings.from_address_help')" />
                <x-form.nested-field name="from_name" :label="trans('mail-settings.from_name')" :value="$value('from_name', $snipeSettings->site_name)" :help="trans('mail-settings.from_name_help')" />
                <x-form.nested-field name="reply_to" type="email" :label="trans('mail-settings.reply_to')" :value="$value('reply_to')" :help="trans('mail-settings.reply_to_help')" />

                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        <button class="btn btn-theme" type="submit">{{ trans('mail-settings.save') }}</button>
                        @if ($row?->updated_by)
                            <p class="help-block">{{ trans('mail-settings.last_change', ['name' => $row->editor?->display_name, 'date' => $when($row->updated_at?->toIso8601String())]) }}</p>
                        @endif
                    </div>
                </div>
            </x-box>
        </form>

        <x-box :header="trans('mail-settings.test_title')">
            <p>{{ trans('mail-settings.test_help') }}</p>
            @if ($last = $status['test'] ?? null)
                <p @class(['text-success' => $last['ok'], 'text-danger' => ! $last['ok']])>
                    {{ $last['ok'] ? trans('mail-settings.last_test_ok', ['date' => $when($last['at']), 'to' => $last['to'] ?? '']) : trans('mail-settings.last_test_failed', ['date' => $when($last['at']), 'error' => $last['error']]) }}
                </p>
            @endif
            <form method="POST" action="{{ route('settings.mail.test') }}" class="return-step-form">
                @csrf
                <input type="hidden" name="_sensitive" value="{{ $sensitive }}">
                <label>
                    <span>{{ trans('mail-settings.test_to') }}</span>
                    <input class="form-control" type="email" name="test_to" value="{{ old('test_to', auth()->user()->email) }}" required>
                </label>
                <button class="btn btn-default" type="submit">{{ trans('mail-settings.test_send') }}</button>
            </form>
            <x-form.error name="test_to" />
        </x-box>

        <form method="POST" action="{{ route('settings.mail.digest') }}" class="form-horizontal">
            @csrf
            @method('PUT')
            <input type="hidden" name="_sensitive" value="{{ $sensitive }}">
            <x-box :header="trans('mail-settings.digest_title')">
                <p>{{ trans('mail-settings.digest_intro') }}</p>
                <p class="help-block">{{ trans('mail-settings.needs_server') }}</p>
                @if ($last = $status['digest'] ?? null)
                    <p @class(['text-success' => $last['ok'], 'text-danger' => ! $last['ok'], 'text-muted' => $last['ok'] && ! ($last['sent'] ?? true)])>
                        @if (! $last['ok'])
                            {{ trans('mail-settings.last_test_failed', ['date' => $when($last['at']), 'error' => $last['error']]) }}
                        @elseif ($last['sent'] ?? true)
                            {{ trans('mail-settings.last_digest', ['date' => $when($last['at'])]) }}
                        @else
                            {{ trans('mail-settings.last_digest_skipped', ['date' => $when($last['at'])]) }}
                        @endif
                    </p>
                @endif

                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        <input type="hidden" name="digest_enabled" value="0">
                        <label class="form-control"><input type="checkbox" name="digest_enabled" value="1" @checked(old('digest_enabled', $digest['enabled'] ?? false))> {{ trans('mail-settings.digest_enabled') }}</label>
                    </div>
                </div>
                <x-form.nested-field name="recipients" type="textarea" :label="trans('mail-settings.digest_recipients')" :value="old('recipients', implode(', ', $digest['recipients'] ?? []))" :help="trans('mail-settings.digest_recipients_help')" />
                <x-form.error name="recipient_list" />
                <x-form.error name="recipient_list.0" />
                <div class="form-group">
                    <label for="field-hour" class="col-md-3 control-label">{{ trans('mail-settings.digest_hour') }}</label>
                    <div class="col-md-3">
                        <select class="form-control" id="field-hour" name="hour">
                            @foreach (range(0, 23) as $hour)
                                <option value="{{ $hour }}" @selected((int) old('hour', $digest['hour'] ?? 7) === $hour)>{{ sprintf('%02d:00', $hour) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7 col-md-offset-3">
                        <p class="help-block">{{ trans('mail-settings.digest_hour_help', ['timezone' => config('app.timezone')]) }}</p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        <button class="btn btn-theme" type="submit">{{ trans('mail-settings.digest_save') }}</button>
                        <button class="btn btn-default" type="submit" form="mail-digest-send">{{ trans('mail-settings.digest_send') }}</button>
                        <p class="help-block">{{ trans('mail-settings.digest_send_help') }}</p>
                    </div>
                </div>
            </x-box>
        </form>

        {{-- "Send now" lives in the summary box; its form cannot nest in the other one. --}}
        <form method="POST" action="{{ route('settings.mail.digest.send') }}" id="mail-digest-send" hidden>
            @csrf
            <input type="hidden" name="_sensitive" value="{{ $sensitive }}">
        </form>
    </x-container>
@stop

@push('js')
    <script nonce="{{ csrf_token() }}">
        (() => {
            const select = document.querySelector('[data-mail-provider]');
            const field = name => document.querySelector('[data-mail-field="' + name + '"]');
            const presets = [...select.options].map(option => JSON.parse(option.dataset.preset || '{}'));
            select.addEventListener('change', () => {
                const preset = JSON.parse(select.selectedOptions[0].dataset.preset || '{}');
                document.querySelectorAll('[data-mail-guide]').forEach(guide => {
                    guide.hidden = guide.dataset.mailGuide !== select.value;
                });
                // Fill from the preset, but never overwrite something typed by hand.
                ['host', 'port', 'username'].forEach(name => {
                    const input = field(name);
                    const fromAPreset = input.value === '' || presets.some(other => String(other[name] ?? '') === input.value);
                    if (fromAPreset) {
                        input.value = preset[name] ?? (name === 'port' ? 587 : '');
                    }
                });
            });
        })();
    </script>
@endpush
