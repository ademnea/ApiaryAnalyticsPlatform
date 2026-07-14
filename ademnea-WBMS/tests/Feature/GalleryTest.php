<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_gallery_index(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.gallery.index'));

        $response->assertStatus(200);
        $response->assertSee('Gallery Albums');
    }

    public function test_admin_routes_redirect_to_login_when_unauthenticated(): void
    {
        $response = $this->get(route('admin.gallery.index'));

        $response->assertRedirect('/login');
    }
}
