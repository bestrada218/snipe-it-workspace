<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_labels', function (Blueprint $table): void {
            $table->string('workflow_key', 32)->nullable()->unique();
        });

        $statuses = [
            ['ready', 'Ready to Assign', 'Ready to Deploy', 1, 0, 0, 'Verified and ready to give to a person or deploy.'],
            ['review', 'Pending Review', 'Pending', 0, 1, 0, 'Awaiting inspection before it can be assigned.'],
            ['setup', 'Pending Setup', null, 0, 1, 0, 'Awaiting configuration before it can be assigned.'],
            ['repair', 'In Repair', 'Out for Repair', 0, 0, 0, 'Under diagnosis or repair.'],
            ['damaged', 'Damaged', 'Broken - Not Fixable', 0, 0, 0, 'Confirmed damage. Awaiting a decision to repair or retire.'],
            ['retired', 'Retired', 'Archived', 0, 0, 1, 'Removed from operational stock. The asset history is kept.'],
        ];

        foreach ($statuses as [$key, $name, $oldName, $deployable, $pending, $archived, $notes]) {
            $labels = DB::table('status_labels')->whereNull('deleted_at');
            $sameName = (clone $labels)->where('name', $name)->first();
            $matching = (clone $labels)->where('deployable', $deployable)->where('pending', $pending)->where('archived', $archived);
            $label = (clone $matching)->where('name', $name)->first();
            if (! $label && $oldName !== null && ! $sameName) {
                $label = $matching->where('name', $oldName)->first();
            }

            $values = ['workflow_key' => $key, 'notes' => $notes, 'updated_at' => now()];
            if ($label) {
                DB::table('status_labels')->where('id', $label->id)->update($values + ['name' => $name]);

                continue;
            }

            $uniqueName = $name;
            for ($suffix = 2; (clone $labels)->where('name', $uniqueName)->exists(); $suffix++) {
                $uniqueName = $name.' ('.$suffix.')';
            }

            DB::table('status_labels')->insert($values + [
                'name' => $uniqueName,
                'deployable' => $deployable,
                'pending' => $pending,
                'archived' => $archived,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('status_labels', function (Blueprint $table): void {
            $table->dropUnique(['workflow_key']);
            $table->dropColumn('workflow_key');
        });
    }
};
