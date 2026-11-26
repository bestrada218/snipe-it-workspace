<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->nullable()->default(null)->change();
        });

        $this->convertSecrets(true);
    }

    public function down(): void
    {
        $this->convertSecrets(false);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('two_factor_secret', 32)->nullable()->default(null)->change();
        });
    }

    private function convertSecrets(bool $encrypt): void
    {
        DB::table('users')->select('id', 'two_factor_secret')->whereNotNull('two_factor_secret')
            ->chunkById(200, function ($users) use ($encrypt): void {
                foreach ($users as $user) {
                    $secret = $user->two_factor_secret;
                    // The previous schema could only store 32 characters. Longer values
                    // are Laravel ciphertexts and can occur when resuming a deployment.
                    if ($encrypt === (mb_strlen($secret) <= 32)) {
                        $converted = $encrypt ? Crypt::encryptString($secret) : Crypt::decryptString($secret);
                        DB::table('users')->where('id', $user->id)->where('two_factor_secret', $secret)
                            ->update(['two_factor_secret' => $converted]);
                    }
                }
            });
    }
};
