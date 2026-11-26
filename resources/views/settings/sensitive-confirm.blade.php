@extends('layouts/default')

@section('title', trans('cloud-backups.confirm_title'))

@section('content')
    <x-container>
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <x-box :header="trans('cloud-backups.confirm_title')">
                    <form method="POST" action="{{ route('sensitive.confirm.store') }}" class="sensitive-confirm" autocomplete="off" data-credential-form>
                        @csrf
                        <p>{{ trans('cloud-backups.confirm_intro', ['minutes' => $minutes]) }}</p>

                        @if ($barcode)
                            <div class="callout callout-info">
                                <p>{{ trans('cloud-backups.enroll_intro') }}</p>
                                <div class="sensitive-confirm-qr">{!! $barcode->getHtmlDiv() !!}</div>
                                <p class="help-block">{{ trans('cloud-backups.enroll_manual') }} <code>{{ $secret }}</code></p>
                            </div>
                        @endif

                        <div @class(['form-group', 'has-error' => $errors->has('password')])>
                            <label for="confirm-password">{{ trans('cloud-backups.your_password') }}</label>
                            <input class="form-control" id="confirm-password" name="password" type="password" autocomplete="current-password" required autofocus>
                            <x-form.error name="password" />
                        </div>
                        <div @class(['form-group', 'has-error' => $errors->has('two_factor_code')])>
                            <label for="confirm-code">{{ trans('cloud-backups.code') }}</label>
                            <input class="form-control" id="confirm-code" name="two_factor_code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" required>
                            <x-form.error name="two_factor_code" />
                        </div>
                        <button class="btn btn-theme" type="submit">{{ trans('cloud-backups.confirm') }}</button>
                    </form>
                </x-box>
            </div>
        </div>
    </x-container>
@stop
