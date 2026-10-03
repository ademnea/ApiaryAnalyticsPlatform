<?php

namespace Tests\Feature\Api\Farmer;

use App\Models\Alert;
use App\Models\Farmer;
use App\Models\FarmerMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regression cover for the identity bug: alerts and messages are keyed by
 * farmers.id, but the controllers passed $request->user()->id — a users.id.
 *
 * These tests only mean anything because makeFarmer() forces users.id and
 * farmers.id apart. If the two sequences line up, passing the wrong one
 * produces correct-looking results and the bug hides.
 */
class IdentityScopingTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    public function test_the_fixture_really_does_separate_user_and_farmer_ids(): void
    {
        [$user, $farmer] = $this->makeFarmer();

        $this->assertNotSame(
            $user->id,
            $farmer->id,
            'Fixture is not exercising the bug: users.id and farmers.id coincide.'
        );
    }

    public function test_alerts_are_scoped_to_the_callers_farmer_record(): void
    {
        [$user, $farmer] = $this->makeFarmer();
        [, $otherFarmer] = $this->makeFarmer('other@example.com', idOffset: 5);

        $mine = Alert::create([
            'farmer_id' => $farmer->id,
            'type'      => 'feed_required',
            'message'   => 'Mine',
            'is_read'   => false,
        ]);

        Alert::create([
            'farmer_id' => $otherFarmer->id,
            'type'      => 'feed_required',
            'message'   => 'Not mine',
            'is_read'   => false,
        ]);

        // An alert numbered after the caller's USER id — what the buggy code
        // would have matched on. Make sure a farmer with that id exists, or the
        // alerts.farmer_id foreign key rejects the row on MySQL.
        $shadow = Farmer::find($user->id) ?? Farmer::factory()->create(['id' => $user->id]);
        Alert::create([
            'farmer_id' => $shadow->id,
            'type'      => 'malfunction',
            'message'   => 'Belongs to whoever farmers.id = users.id is',
            'is_read'   => false,
        ]);

        $response = $this->authed($user)->getJson('/api/v1/farmer/alerts')->assertOk();

        $ids = array_column($response->json('data.data'), 'id');

        $this->assertSame([$mine->id], $ids);
    }

    public function test_marking_another_farmers_alert_read_is_denied(): void
    {
        [$user] = $this->makeFarmer();
        [, $otherFarmer] = $this->makeFarmer('other@example.com', idOffset: 5);

        $theirs = Alert::create([
            'farmer_id' => $otherFarmer->id,
            'type'      => 'feed_required',
            'message'   => 'Not yours',
            'is_read'   => false,
        ]);

        $this->authed($user)
            ->patchJson("/api/v1/farmer/alerts/{$theirs->id}/read")
            ->assertStatus(403);

        $this->assertFalse((bool) $theirs->fresh()->is_read);
    }

    /**
     * The worst form of the bug: messages were written with the wrong owner,
     * so the admin inbox attributed them to another farmer.
     */
    public function test_submitted_message_is_stamped_with_the_correct_farmer_id(): void
    {
        [$user, $farmer] = $this->makeFarmer();

        $this->authed($user)
            ->postJson('/api/v1/farmer/messages', [
                'subject' => 'Hive 3 looks weak',
                'message' => 'Please advise.',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('farmer_messages', [
            'farmer_id' => $farmer->id,
            'subject'   => 'Hive 3 looks weak',
        ]);

        $this->assertDatabaseMissing('farmer_messages', [
            'farmer_id' => $user->id,
            'subject'   => 'Hive 3 looks weak',
        ]);
    }

    public function test_message_listing_is_scoped_to_the_caller(): void
    {
        [$user, $farmer] = $this->makeFarmer();
        [, $otherFarmer] = $this->makeFarmer('other@example.com', idOffset: 5);

        FarmerMessage::create([
            'farmer_id' => $farmer->id,
            'subject'   => 'Mine',
            'message'   => 'x',
            'status'    => 'sent',
        ]);

        FarmerMessage::create([
            'farmer_id' => $otherFarmer->id,
            'subject'   => 'Not mine',
            'message'   => 'x',
            'status'    => 'sent',
        ]);

        $response = $this->authed($user)->getJson('/api/v1/farmer/messages')->assertOk();

        $subjects = array_column($response->json('data.data'), 'subject');

        $this->assertSame(['Mine'], $subjects);
    }
}
