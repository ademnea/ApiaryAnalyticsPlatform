<?php

namespace Tests\Feature\Api\Farmer;

use App\Models\Alert;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveWeight;
use App\Models\IotDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Api\Farmer\Concerns\CreatesFarmerAccounts;
use Tests\TestCase;

/**
 * Farmer API data endpoints. Ownership is hive → apiary → farmer, and every
 * endpoint must only ever return the authenticated farmer's own records.
 */
class ApiaryApiTest extends TestCase
{
    use RefreshDatabase;
    use CreatesFarmerAccounts;

    public function test_farmer_sees_only_their_own_apiaries(): void
    {
        $me = $this->createFarmerAccount();
        $mine = Apiary::factory()->create(['farmer_id' => $me['farmer']->id]);
        Apiary::factory()->create(); // someone else's

        $this->asFarmer($me)->getJson('/api/v1/farmer/apiaries')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_farmer_can_list_hives_in_their_apiary(): void
    {
        $me = $this->createFarmerAccount();
        $apiary = Apiary::factory()->create(['farmer_id' => $me['farmer']->id]);
        Hive::factory()->count(2)->create(['apiary_id' => $apiary->id]);

        $this->asFarmer($me)->getJson("/api/v1/farmer/apiaries/{$apiary->id}/hives")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_farmer_cannot_list_hives_in_someone_elses_apiary(): void
    {
        $me = $this->createFarmerAccount();
        $theirs = Apiary::factory()->create();

        $this->asFarmer($me)->getJson("/api/v1/farmer/apiaries/{$theirs->id}/hives")->assertNotFound();
    }

    public function test_farmer_can_read_sensor_data_only_for_their_own_hives(): void
    {
        $me = $this->createFarmerAccount();
        $myHive = Hive::factory()->create(['apiary_id' => Apiary::factory()->create(['farmer_id' => $me['farmer']->id])->id]);
        $theirHive = Hive::factory()->create(['apiary_id' => Apiary::factory()->create()->id]);

        $device = IotDevice::factory()->create(['hive_id' => $myHive->id]);
        HiveWeight::create(['hive_id' => $myHive->id, 'device_id' => $device->id, 'weight_kg' => 21.5, 'recorded_at' => now()->subHour()]);
        HiveWeight::create(['hive_id' => $myHive->id, 'device_id' => $device->id, 'weight_kg' => 22.0, 'recorded_at' => now()]);

        $this->asFarmer($me)->getJson("/api/v1/farmer/hives/{$myHive->id}/weight")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.weight_kg', 22); // newest reading first

        $this->asFarmer($me)->getJson("/api/v1/farmer/hives/{$myHive->id}/latest")
            ->assertOk()
            ->assertJsonPath('data.weight.weight_kg', 22);

        $this->asFarmer($me)->getJson("/api/v1/farmer/hives/{$theirHive->id}/weight")->assertNotFound();
    }

    public function test_alerts_are_scoped_to_the_farmer_not_the_user_id(): void
    {
        $me = $this->createFarmerAccount();
        $this->assertNotSame($me['user']->id, $me['farmer']->id, 'helper must make ids differ');

        $hive = Hive::factory()->create(['apiary_id' => Apiary::factory()->create(['farmer_id' => $me['farmer']->id])->id]);
        $mine = Alert::create(['farmer_id' => $me['farmer']->id, 'hive_id' => $hive->id, 'type' => 'feed_required', 'message' => 'Feed me']);
        // If the API looked alerts up by user id instead of farmer id, it would miss $mine.
        $notMine = Alert::create(['farmer_id' => Farmer::factory()->create()->id, 'type' => 'malfunction', 'message' => 'Not yours']);

        $this->asFarmer($me)->getJson('/api/v1/farmer/alerts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);

        $this->asFarmer($me)->patchJson("/api/v1/farmer/alerts/{$mine->id}/read")->assertOk();
        $this->assertTrue($mine->fresh()->is_read);

        $this->asFarmer($me)->patchJson("/api/v1/farmer/alerts/{$notMine->id}/read")->assertForbidden();
    }

    public function test_messages_are_stored_against_the_farmer_and_may_only_reference_own_hives(): void
    {
        $me = $this->createFarmerAccount();
        $myHive = Hive::factory()->create(['apiary_id' => Apiary::factory()->create(['farmer_id' => $me['farmer']->id])->id]);
        $theirHive = Hive::factory()->create(['apiary_id' => Apiary::factory()->create()->id]);

        $this->asFarmer($me)->postJson('/api/v1/farmer/messages', [
            'subject' => 'Queen missing',
            'message' => 'Hive looks queenless.',
            'hive_id' => $myHive->id,
        ])->assertCreated();

        $this->assertDatabaseHas('farmer_messages', [
            'farmer_id' => $me['farmer']->id,
            'hive_id' => $myHive->id,
            'subject' => 'Queen missing',
        ]);

        $this->asFarmer($me)->postJson('/api/v1/farmer/messages', [
            'subject' => 'Sneaky',
            'message' => 'Not my hive.',
            'hive_id' => $theirHive->id,
        ])->assertNotFound();

        $this->asFarmer($me)->getJson('/api/v1/farmer/messages')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_page_size_is_capped(): void
    {
        $me = $this->createFarmerAccount();

        $this->asFarmer($me)->getJson('/api/v1/farmer/apiaries?per_page=100000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_data_endpoints_require_a_farmer_token(): void
    {
        $this->getJson('/api/v1/farmer/apiaries')->assertUnauthorized();
    }
}
