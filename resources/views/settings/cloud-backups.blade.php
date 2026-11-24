@extends('layouts/default')

@section('title', trans('cloud-backups.title'))

@php
    $secretPlaceholder = trans('cloud-backups.saved_secret');
    $value = fn (string $key, $default = null) => old($key, data_get($saved, $key, $default));
@endphp

@section('content')
    <x-container>
        <x-box :header="trans('cloud-backups.status')">
            <p>{{ trans('cloud-backups.intro') }}</p>

            @if ($unreadable)
                <p class="callout callout-danger">{{ trans('cloud-backups.unreadable') }}</p>
            @endif

            <p @class(['callout', 'callout-success' => $hasPassword, 'callout-warning' => ! $hasPassword])>
                {{ $hasPassword ? trans('cloud-backups.password_status_ok') : trans('cloud-backups.password_status_missing') }}
            </p>

            <ul class="cloud-backup-status">
                @foreach ($destinations as $key => $destination)
                    <li>
                        <div class="cloud-backup-status-head">
                            <strong>{{ trans('cloud-backups.destinations.'.$key) }}</strong>
                            <span class="workspace-shipment-status" data-status="{{ ($destination['enabled'] || $destination['in_env']) ? 'received' : 'cancelled' }}">
                                {{ ($destination['enabled'] || $destination['in_env']) ? trans('cloud-backups.enabled') : trans('cloud-backups.disabled') }}
                            </span>
                        </div>
                        @if ($destination['in_env'])
                            <p class="help-block">{{ trans('cloud-backups.from_env') }}</p>
                        @endif
                        @if ($destination['enabled'] || $destination['in_env'])
                            @if ($destination['last'])
                                <p @class(['text-success' => $destination['last']['ok'], 'text-danger' => ! $destination['last']['ok']])>
                                    @php($when = Helper::getFormattedDateObject($destination['last']['at'], 'datetime', false))
                                    {{ $destination['last']['ok'] ? trans('cloud-backups.last_ok', ['date' => $when]) : trans('cloud-backups.last_failed', ['date' => $when, 'error' => $destination['last']['error']]) }}
                                </p>
                            @else
                                <p class="text-muted">{{ trans('cloud-backups.never') }}</p>
                            @endif
                            <form method="POST" action="{{ route('settings.cloud_backups.test', $key) }}">
                                @csrf
                                <button class="btn btn-default btn-sm" type="submit">{{ trans('cloud-backups.test') }}</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if ($row?->updated_by)
                <p class="text-muted">{{ trans('cloud-backups.last_change', ['name' => $row->editor?->display_name, 'date' => Helper::getFormattedDateObject($row->updated_at, 'datetime', false)]) }}</p>
            @endif
        </x-box>

        <form method="POST" action="{{ route('settings.cloud_backups.save') }}" class="form-horizontal" enctype="multipart/form-data" autocomplete="off">
            @csrf
            @method('PUT')

            <x-box :header="trans('cloud-backups.password_title')">
                <x-form.nested-field name="password" type="password" :label="trans('cloud-backups.password')" :placeholder="\App\Services\Backups\CloudBackupSettings::has('password') ? $secretPlaceholder : null" :help="trans('cloud-backups.password_help')" autocomplete="new-password" minlength="12" />
                <x-form.nested-field name="password_confirmation" type="password" :label="trans('cloud-backups.password_confirmation')" autocomplete="new-password" />
            </x-box>

            <x-box :header="trans('cloud-backups.destinations.s3')">
                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        <label class="form-control"><input type="checkbox" name="s3[enabled]" value="1" @checked($value('s3.enabled'))> {{ trans('cloud-backups.enable') }}</label>
                        <p class="help-block">{{ trans('cloud-backups.s3_help') }}</p>
                    </div>
                </div>
                <x-form.nested-field name="s3.key" :label="trans('cloud-backups.s3_key')" :value="$value('s3.key')" />
                <x-form.nested-field name="s3.secret" type="password" :label="trans('cloud-backups.s3_secret')" :placeholder="\App\Services\Backups\CloudBackupSettings::has('s3.secret') ? $secretPlaceholder : null" autocomplete="new-password" />
                <x-form.nested-field name="s3.region" :label="trans('cloud-backups.s3_region')" :value="$value('s3.region', 'us-east-1')" />
                <x-form.nested-field name="s3.bucket" :label="trans('cloud-backups.s3_bucket')" :value="$value('s3.bucket')" />
                <x-form.nested-field name="s3.root" :label="trans('cloud-backups.s3_root')" :value="$value('s3.root')" />
                <x-form.nested-field name="s3.endpoint" type="url" :label="trans('cloud-backups.s3_endpoint')" :value="$value('s3.endpoint')" :help="trans('cloud-backups.s3_endpoint_help')" placeholder="https://" />
                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        <label class="form-control"><input type="checkbox" name="s3[path_style]" value="1" @checked($value('s3.path_style'))> {{ trans('cloud-backups.s3_path_style') }}</label>
                    </div>
                </div>
            </x-box>

            <x-box :header="trans('cloud-backups.destinations.google')">
                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        <label class="form-control"><input type="checkbox" name="google[enabled]" value="1" @checked($value('google.enabled'))> {{ trans('cloud-backups.enable') }}</label>
                        <p class="help-block">{{ trans('cloud-backups.google_help') }}</p>
                    </div>
                </div>
                <div @class(['form-group', 'has-error' => $errors->has('google.service_account_file')])>
                    <label for="field-google-service_account_file" class="col-md-3 control-label">{{ trans('cloud-backups.google_account') }}</label>
                    <div class="col-md-7">
                        <input id="field-google-service_account_file" name="google[service_account_file]" type="file" accept="application/json,.json">
                        <p class="help-block">{{ trans('cloud-backups.google_account_help') }}</p>
                        <x-form.error name="google.service_account_file" />
                    </div>
                </div>
                <x-form.nested-field name="google.service_account_json" type="textarea" :label="trans('cloud-backups.google_account')" :placeholder="\App\Services\Backups\CloudBackupSettings::has('google.service_account_json') ? $secretPlaceholder : trans('cloud-backups.google_account_placeholder')" />
                <x-form.nested-field name="google.folder_id" :label="trans('cloud-backups.google_folder')" :value="$value('google.folder_id')" :help="trans('cloud-backups.google_folder_help')" />
                <div class="form-group">
                    <div class="col-md-7 col-md-offset-3">
                        <label class="form-control"><input type="checkbox" name="google[shared_drive]" value="1" @checked($value('google.shared_drive', true))> {{ trans('cloud-backups.google_shared') }}</label>
                    </div>
                </div>
            </x-box>

            <div class="box-footer text-right">
                <a class="btn btn-default" href="{{ route('settings.backups.index') }}">{{ trans('general.cancel') }}</a>
                <button class="btn btn-theme" type="submit">{{ trans('cloud-backups.save') }}</button>
            </div>
        </form>
    </x-container>
@stop
