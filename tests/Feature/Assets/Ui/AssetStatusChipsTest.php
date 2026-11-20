<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class AssetStatusChipsTest extends TestCase
{
    public function test_chips_keep_the_selected_company(): void
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        Asset::factory()->count(2)->create(['company_id' => $companyA->id]);
        Asset::factory()->count(3)->create(['company_id' => $companyB->id]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.index', ['company_id' => $companyA->id]))
            ->assertOk()
            ->assertViewHas('statusFilters', function (array $filters) use ($companyA): bool {
                $this->assertSame(2, $filters[0]['count']);
                $this->assertStringContainsString('company_id='.$companyA->id, $filters[1]['url']);
                $this->assertStringContainsString('status_type=Deployed', $filters[1]['url']);

                return true;
            });
    }

    public function test_chips_keep_the_order_number(): void
    {
        Asset::factory()->count(2)->create(['order_number' => 'PO-7731']);
        Asset::factory()->create(['order_number' => 'PO-1000']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('hardware.index', ['order_number' => 'PO-7731']))
            ->assertOk()
            ->assertViewHas('statusFilters', function (array $filters): bool {
                $this->assertSame(2, $filters[0]['count']);
                $this->assertStringContainsString('order_number=PO-7731', $filters[2]['url']);

                return true;
            });
    }
}
