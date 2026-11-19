<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetShipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetShipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AssetShipment>
     */
    protected $model = AssetShipment::class;

    /**
     * Define the model's default state: an open shipment still in transit.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'recipient_id' => User::factory(),
            'created_by' => User::factory()->superuser(),
            'platform' => 'Rippling',
            'carrier' => $this->faker->company(),
            'tracking_number' => (string) $this->faker->unique()->numerify('##########'),
            'purchase_approver_name' => $this->faker->name(),
            'approval_reference' => 'Aprobación por correo',
            'approval_key' => $this->faker->uuid().'.pdf',
            'approval_filename' => 'aprobacion.pdf',
            'expected_on' => now()->addWeek()->toDateString(),
        ];
    }

    public function received(): static
    {
        return $this->state(fn () => ['received_at' => now()]);
    }

    public function assigned(): static
    {
        return $this->state(fn () => ['received_at' => now(), 'assigned_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['cancelled_at' => now()]);
    }
}
