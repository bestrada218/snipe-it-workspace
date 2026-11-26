<?php

namespace Database\Seeders;

use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Database\Seeder;

class StatuslabelSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        Statuslabel::query()->whereNotNull('workflow_key')->whereNull('created_by')
            ->update(['created_by' => $admin->id]);

        foreach ([Statuslabel::factory()->outForDiagnostics(), Statuslabel::factory()->lost()] as $factory) {
            $attributes = $factory->raw(['created_by' => $admin->id]);
            if (! Statuslabel::where('name', $attributes['name'])->exists()) {
                $factory->create($attributes);
            }
        }
    }
}
