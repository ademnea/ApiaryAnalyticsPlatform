<?php

namespace Tests\Feature\Admin;

use App\Models\Farmer;
use App\Models\FarmerMessage;
use App\Models\Hive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class FarmerMessagesTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function admin_can_view_messages_index(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        FarmerMessage::factory()->count(3)->create();

        $response = $this->get(route('admin.farmers.messages'));

        $response->assertOk();
        $response->assertViewHas('messages');
    }

    #[Test]
    public function messages_page_shows_message_details(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $farmer = Farmer::factory()->create();
        $message = FarmerMessage::factory()->for($farmer)->create();

        $response = $this->get(route('admin.farmers.messages.show', $message));

        $response->assertOk();
        $response->assertSee($message->subject);
        $response->assertSee($farmer->full_name);
    }

    #[Test]
    public function viewing_message_marks_it_as_read(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $message = FarmerMessage::factory()->create(['status' => 'sent']);

        $this->get(route('admin.farmers.messages.show', $message));

        $this->assertDatabaseHas('farmer_messages', [
            'id' => $message->id,
            'status' => 'read',
        ]);
    }

    #[Test]
    public function admin_can_resolve_a_message(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $message = FarmerMessage::factory()->create(['status' => 'sent']);

        $response = $this->patch(route('admin.farmers.messages.resolve', $message));

        $response->assertRedirect(route('admin.farmers.messages'));
        $this->assertDatabaseHas('farmer_messages', [
            'id' => $message->id,
            'status' => 'resolved',
        ]);
    }

    #[Test]
    public function guest_cannot_access_messages(): void
    {
        $response = $this->get(route('admin.farmers.messages'));

        $response->assertRedirect(route('login'));
    }
}
