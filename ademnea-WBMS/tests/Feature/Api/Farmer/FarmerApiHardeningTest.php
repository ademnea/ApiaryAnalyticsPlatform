<?php

namespace Tests\Feature\Api\Farmer;

use App\Models\Apiary;
use App\Models\Hive;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FarmerApiHardeningTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    public function test_a_message_may_reference_the_farmers_own_hive(): void
    {
        [$user, $farmer] = $this->makeFarmer();
        $hive = Hive::factory()->create(['apiary_id' => Apiary::factory()->create(['farmer_id' => $farmer->id])->id]);

        $this->authed($user)->postJson('/api/v1/farmer/messages', [
            'subject' => 'Queen missing',
            'message' => 'Looks queenless.',
            'hive_id' => $hive->id,
        ])->assertCreated();

        $this->assertDatabaseHas('farmer_messages', ['farmer_id' => $farmer->id, 'hive_id' => $hive->id]);
    }

    public function test_a_message_cannot_reference_another_farmers_hive(): void
    {
        [$user] = $this->makeFarmer();
        $theirHive = Hive::factory()->create(['apiary_id' => Apiary::factory()->create()->id]);

        $this->authed($user)->postJson('/api/v1/farmer/messages', [
            'subject' => 'Sneaky',
            'message' => 'Not my hive.',
            'hive_id' => $theirHive->id,
        ])->assertNotFound();

        $this->assertDatabaseCount('farmer_messages', 0);
    }

    public function test_page_size_is_capped(): void
    {
        [$user] = $this->makeFarmer();

        foreach (['apiaries', 'alerts', 'messages'] as $endpoint) {
            $response = $this->authed($user)->getJson("/api/v1/farmer/{$endpoint}?per_page=1000000")->assertOk();
            $perPage = $response->json('meta.per_page') ?? $response->json('data.per_page');
            $this->assertSame(100, $perPage, "{$endpoint} per_page should be capped at 100");
        }
    }
}
