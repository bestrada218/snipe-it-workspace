<?php

namespace App\Listeners;

use App\Enums\ActionType;
use App\Models\Actionlog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User || $event->guard !== 'web') {
            return;
        }

        if (! request()->routeIs('users.impersonate.*') && ! $event->user->two_factor_active()) {
            self::recordSessionActivity($event->user, ActionType::LoggedIn);
        }

        $now = new Carbon;

        try {
            DB::table('login_attempts')->insert(
                [
                    'username' => $event->user->username,
                    'user_agent' => request()->header('User-Agent'),
                    'remote_ip' => request()->ip(),
                    'successful' => 1,
                    'created_at' => $now,
                ]
            );
        } catch (\Exception $e) {
            Log::debug($e);
        }
    }

    public static function recordSessionActivity(User $user, ActionType $action): void
    {
        try {
            $log = new Actionlog;
            $log->created_by = $user->id;
            $log->item_id = $user->id;
            $log->item_type = User::class;
            $log->logaction($action);
        } catch (\Exception $exception) {
            report($exception);
        }
    }
}
