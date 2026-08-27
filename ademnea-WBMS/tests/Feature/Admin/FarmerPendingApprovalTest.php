<?php

namespace Tests\Feature\Admin;

use App\Models\Farmer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class FarmerPendingApprovalTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    #[Test]
    public function admin_can_view_pending_farmers(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        Farmer::factory()->count(2)->create(['profile_status' => 'pending']);
        Farmer::factory()->count(3)->create(['profile_status' => 'active']);

        $response = $this->get(route('admin.farmers.pending'));

        $response->assertOk();
        $response->assertViewHas('farmers');
        $response->assertSee('Pending Farmer Approvals');
    }

    #[Test]
    public function pending_page_only_shows_pending_farmers(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $pendingFarmer = Farmer::factory()->create(['profile_status' => 'pending']);
        $activeFarmer = Farmer::factory()->create(['profile_status' => 'active']);

        $response = $this->get(route('admin.farmers.pending'));

        $response->assertOk();
        $response->assertSee($pendingFarmer->full_name);
        $response->assertDontSee($activeFarmer->full_name);
    }

    #[Test]
    public function admin_can_approve_a_pending_farmer(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $farmer = Farmer::factory()->create(['profile_status' => 'pending']);

        $response = $this->post(route('admin.farmers.approve', $farmer));

        $response->assertRedirect(route('admin.farmers.pending'));
        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'profile_status' => 'active',
        ]);
    }

    #[Test]
    public function admin_can_reject_a_pending_farmer(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');
        $farmer = Farmer::factory()->create(['profile_status' => 'pending']);

        $response = $this->post(route('admin.farmers.reject', $farmer));

        $response->assertRedirect(route('admin.farmers.pending'));
        $this->assertDatabaseHas('farmers', [
            'id' => $farmer->id,
            'profile_status' => 'incomplete',
        ]);
    }

    #[Test]
    public function pending_page_shows_no_pending_message_when_empty(): void
    {
        $this->actingAsAdminWithPermission('manage-farmers');

        $response = $this->get(route('admin.farmers.pending'));

        $response->assertOk();
        $response->assertSee('No pending farmers');
    }

    #[Test]
    public function guest_cannot_access_pending_approvals(): void
    {
        $response = $this->get(route('admin.farmers.pending'));

        $response->assertRedirect(route('admin.login'));
    }
}
