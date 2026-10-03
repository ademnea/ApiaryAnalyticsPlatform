<?php

namespace Tests\Feature\Api\Farmer;

use App\Models\Apiary;
use App\Models\Hive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * A farmer records where a hive stands, from the phone held next to it.
 */
class HiveLocationTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Hive} */
    private function farmerWithHive(string $email = 'farmer@example.com', bool $canWrite = true): array
    {
        [$user, $farmer] = $this->makeFarmer($email);

        if ($canWrite) {
            $user->assignRole('farmer-write');
        }

        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id, 'latitude' => 1.0, 'longitude' => 30.0, 'accuracy_meters' => 5]);

        return [$user, $hive];
    }

    public function test_a_farmer_sets_the_location_of_their_own_hive(): void
    {
        [$user, $hive] = $this->farmerWithHive();

        $this->authed($user)
            ->putJson("/api/v1/farmer/hives/{$hive->id}/location", ['latitude' => 0.3476123, 'longitude' => 32.5825456, 'accuracy_meters' => 8.4])
            ->assertOk()
            ->assertJsonPath('message', 'Hive location updated.')
            ->assertJsonPath('data.hive_id', $hive->id)
            ->assertJsonPath('data.latitude', 0.3476123)
            ->assertJsonPath('data.longitude', 32.5825456)
            ->assertJsonPath('data.accuracy_meters', 8.4);

        $this->assertDatabaseHas('hives', ['id' => $hive->id, 'latitude' => 0.3476123, 'longitude' => 32.5825456, 'accuracy_meters' => 8.4]);
        $this->assertDatabaseHas('farmer_audit_logs', ['action_type' => 'hive_location_updated', 'affected_record_type' => 'hive', 'affected_record_id' => $hive->id]);
    }

    public function test_a_position_sent_without_accuracy_clears_the_old_accuracy(): void
    {
        [$user, $hive] = $this->farmerWithHive();

        $this->authed($user)
            ->putJson("/api/v1/farmer/hives/{$hive->id}/location", ['latitude' => 0.35, 'longitude' => 32.58])
            ->assertOk()
            ->assertJsonPath('data.accuracy_meters', null);

        $this->assertNull($hive->fresh()->accuracy_meters);
    }

    public function test_a_farmer_without_the_write_role_cannot_move_a_hive(): void
    {
        [$user, $hive] = $this->farmerWithHive(canWrite: false);

        $this->authed($user)
            ->putJson("/api/v1/farmer/hives/{$hive->id}/location", ['latitude' => 0.35, 'longitude' => 32.58])
            ->assertForbidden();

        $this->assertSame(1.0, (float) $hive->fresh()->latitude);
    }

    public function test_a_farmer_cannot_move_another_farmers_hive(): void
    {
        [, $theirHive] = $this->farmerWithHive('other@example.com');
        [$user] = $this->farmerWithHive('me@example.com');

        $this->authed($user)
            ->putJson("/api/v1/farmer/hives/{$theirHive->id}/location", ['latitude' => 0.35, 'longitude' => 32.58])
            ->assertNotFound();

        $this->assertSame(1.0, (float) $theirHive->fresh()->latitude);
    }

    public function test_impossible_or_imprecise_positions_are_rejected(): void
    {
        [$user, $hive] = $this->farmerWithHive();

        $this->authed($user)
            ->putJson("/api/v1/farmer/hives/{$hive->id}/location", ['latitude' => 95, 'longitude' => 200])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);

        $this->authed($user)
            ->putJson("/api/v1/farmer/hives/{$hive->id}/location", ['latitude' => 0.35])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['longitude']);

        $this->authed($user)
            ->putJson("/api/v1/farmer/hives/{$hive->id}/location", ['latitude' => 0.35, 'longitude' => 32.58, 'accuracy_meters' => 850])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['accuracy_meters']);

        $this->assertSame(1.0, (float) $hive->fresh()->latitude);
    }
}
