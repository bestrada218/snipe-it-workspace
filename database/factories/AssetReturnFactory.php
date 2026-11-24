<?php

namespace Database\Factories;

use App\Models\AssetReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetReturn>
 */
class AssetReturnFactory extends Factory
{
    protected $model = AssetReturn::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reason' => 'termination',
            'ticket_number' => 'INC-'.$this->faker->numerify('####'),
            'created_by' => User::factory()->superuser(),
        ];
    }
}
