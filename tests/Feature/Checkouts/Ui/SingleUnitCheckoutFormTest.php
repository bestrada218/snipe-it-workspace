<?php

namespace Tests\Feature\Checkouts\Ui;

use App\Models\Accessory;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\User;
use Tests\TestCase;

/**
 * Items are picked one by one by their own tag, so the checkout forms never
 * ask how many units to hand out: they always send a single unit.
 */
class SingleUnitCheckoutFormTest extends TestCase
{
    public function test_accessory_checkout_form_does_not_ask_for_a_quantity(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('accessories.checkout.show', Accessory::factory()->create(['qty' => 5])))
            ->assertOk()
            ->assertSee('<input type="hidden" name="checkout_qty" value="1">', false)
            ->assertDontSee('id="checkout_qty"', false);
    }

    public function test_consumable_checkout_form_does_not_ask_for_a_quantity(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('consumables.checkout.show', Consumable::factory()->create(['qty' => 5])))
            ->assertOk()
            ->assertSee('<input type="hidden" name="checkout_qty" value="1">', false)
            ->assertDontSee('id="checkout_qty"', false);
    }

    public function test_component_checkout_form_does_not_ask_for_a_quantity(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.checkout.show', Component::factory()->create(['qty' => 5])))
            ->assertOk()
            ->assertSee('<input type="hidden" name="assigned_qty" value="1">', false)
            ->assertDontSee('id="assigned_qty"', false);
    }

    public function test_accessory_checkout_without_quantity_hands_out_one_unit(): void
    {
        $accessory = Accessory::factory()->create(['qty' => 5]);
        $person = User::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('accessories.checkout.store', $accessory), [
                'checkout_to_type' => 'user',
                'assigned_user' => $person->id,
                'checkout_qty' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $accessory->checkouts()->where('assigned_to', $person->id)->count());
    }
}
