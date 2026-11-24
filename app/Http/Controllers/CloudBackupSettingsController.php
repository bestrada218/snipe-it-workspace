<?php

namespace App\Http\Controllers;

use App\Services\Backups\CloudBackupSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * Settings → Backups → Cloud copies: where backups are copied off the
 * server. Superadmins only, behind a fresh password and two-factor check.
 */
class CloudBackupSettingsController extends Controller
{
    public function index(): View
    {
        // Before apply(): what is configured in .env alone.
        $envDisks = config('backup.backup.destination.disks', []);
        CloudBackupSettings::apply();
        $saved = CloudBackupSettings::saved();

        $destinations = [];
        foreach (CloudBackupSettings::DISKS as $key => $disk) {
            $destinations[$key] = [
                'disk' => $disk,
                'enabled' => (bool) ($saved[$key]['enabled'] ?? false),
                'in_env' => in_array($disk, $envDisks, true),
                'last' => CloudBackupSettings::results()[$disk] ?? null,
            ];
        }

        return view('settings.cloud-backups', [
            'saved' => $saved,
            'destinations' => $destinations,
            'hasPassword' => filled(config('backup.backup.password')),
            'unreadable' => CloudBackupSettings::unreadable(),
            'row' => CloudBackupSettings::row(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (config('app.lock_passwords')) {
            return redirect()->route('settings.cloud_backups.index')->with('error', trans('general.feature_disabled'));
        }

        if ($request->hasFile('google.service_account_file')) {
            $request->merge(['google' => array_merge((array) $request->input('google'), [
                'service_account_json' => (string) file_get_contents($request->file('google.service_account_file')->getRealPath()),
            ])]);
        }

        $request->validate([
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            's3.key' => ['nullable', 'string', 'max:255'],
            's3.secret' => ['nullable', 'string', 'max:255'],
            's3.region' => ['nullable', 'string', 'max:64'],
            's3.bucket' => ['nullable', 'string', 'max:255'],
            's3.endpoint' => ['nullable', 'url:https,http', 'max:255'],
            's3.root' => ['nullable', 'string', 'max:255'],
            'google.service_account_file' => ['nullable', 'file', 'max:20'],
            'google.service_account_json' => ['nullable', 'string', 'max:20000'],
            'google.folder_id' => ['nullable', 'string', 'max:255'],
        ], [], [
            'password' => trans('cloud-backups.password'),
        ]);

        $validator = validator([], []);
        $this->checkDestinations($request, $validator);
        if ($validator->errors()->isNotEmpty()) {
            return back()->withErrors($validator)->withInput($request->except(['password', 'password_confirmation', 's3.secret', 'google.service_account_json']));
        }

        CloudBackupSettings::save($request->all(), $request->user());
        Log::info("Cloud backup settings changed by user {$request->user()->id}.");

        return redirect()->route('settings.cloud_backups.index')->with('success', trans('cloud-backups.saved'));
    }

    public function test(Request $request, string $destination): RedirectResponse
    {
        abort_unless(array_key_exists($destination, CloudBackupSettings::DISKS), 404);

        $problem = CloudBackupSettings::test($destination);

        return redirect()->route('settings.cloud_backups.index')->with(
            $problem ? 'error' : 'success',
            $problem
                ? trans('cloud-backups.test_failed', ['destination' => trans('cloud-backups.destinations.'.$destination), 'error' => $problem])
                : trans('cloud-backups.test_ok', ['destination' => trans('cloud-backups.destinations.'.$destination)])
        );
    }

    /**
     * An enabled destination needs everything it uses, counting secrets
     * already saved; any destination needs a backup password.
     */
    private function checkDestinations(Request $request, Validator $validator): void
    {
        $s3 = (bool) $request->input('s3.enabled');
        $google = (bool) $request->input('google.enabled');

        if (($s3 || $google) && blank($request->input('password')) && ! CloudBackupSettings::has('password')) {
            $validator->errors()->add('password', trans('cloud-backups.password_required'));
        }

        if ($s3) {
            foreach (['key', 'bucket'] as $field) {
                if (blank($request->input("s3.{$field}"))) {
                    $validator->errors()->add("s3.{$field}", trans('cloud-backups.required'));
                }
            }
            if (blank($request->input('s3.secret')) && ! CloudBackupSettings::has('s3.secret')) {
                $validator->errors()->add('s3.secret', trans('cloud-backups.required'));
            }
        }

        if ($google) {
            if (blank($request->input('google.folder_id'))) {
                $validator->errors()->add('google.folder_id', trans('cloud-backups.required'));
            }
            $json = $request->input('google.service_account_json');
            if (filled($json)) {
                $account = json_decode((string) $json, true);
                if (! is_array($account) || ($account['type'] ?? null) !== 'service_account' || blank($account['client_email'] ?? null) || blank($account['private_key'] ?? null)) {
                    $validator->errors()->add('google.service_account_json', trans('cloud-backups.invalid_service_account'));
                }
            } elseif (! CloudBackupSettings::has('google.service_account_json')) {
                $validator->errors()->add('google.service_account_json', trans('cloud-backups.required'));
            }
        }
    }
}
