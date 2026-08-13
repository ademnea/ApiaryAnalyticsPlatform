<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $permissions = [
                'manage-gallery',
            ];

            foreach ($permissions as $permission) {
                \Spatie\Permission\Models\Permission::firstOrCreate(
                    ['name' => $permission, 'guard_name' => 'web']
                );
            }

            $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'admin', 'guard_name' => 'web']
            );
            $adminRole->syncPermissions($permissions);
        }
    }

    public function test_admin_can_view_gallery_index(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        $response = $this->actingAs($user)->get(route('admin.gallery.index'));

        $response->assertStatus(200);
        $response->assertSee('Gallery Albums');
    }

    public function test_admin_routes_redirect_to_login_when_unauthenticated(): void
    {
        $response = $this->get(route('admin.gallery.index'));

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_create_album_with_public_visibility(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        $response = $this->actingAs($user)->post(route('admin.gallery.store'), [
            'title' => 'Test Album',
            'description' => 'A test album',
            'category' => 'Events',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $response->assertRedirect(route('admin.gallery.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('gallery_albums', [
            'title' => 'Test Album',
            'visibility' => 'public',
            'category' => 'Events',
        ]);
    }

    public function test_admin_can_create_album_with_private_visibility(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        $response = $this->actingAs($user)->post(route('admin.gallery.store'), [
            'title' => 'Private Album',
            'visibility' => 'private',
            'is_published' => true,
        ]);

        $response->assertRedirect(route('admin.gallery.index'));

        $this->assertDatabaseHas('gallery_albums', [
            'title' => 'Private Album',
            'visibility' => 'private',
        ]);
    }

    public function test_album_creation_requires_visibility(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        $response = $this->actingAs($user)->post(route('admin.gallery.store'), [
            'title' => 'Test Album',
            'is_published' => true,
        ]);

        $response->assertSessionHasErrors('visibility');
    }

    public function test_album_creation_requires_title(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        $response = $this->actingAs($user)->post(route('admin.gallery.store'), [
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_admin_can_update_album_visibility(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        $album = \App\Models\GalleryAlbum::create([
            'user_id' => $user->id,
            'title' => 'Original Title',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->put(route('admin.gallery.update', $album), [
            'title' => 'Updated Title',
            'visibility' => 'private',
            'description' => 'Updated description',
            'is_published' => true,
        ]);

        $response->assertRedirect(route('admin.gallery.edit', $album));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('gallery_albums', [
            'id' => $album->id,
            'title' => 'Updated Title',
            'visibility' => 'private',
        ]);
    }

    public function test_admin_can_delete_album(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        $album = \App\Models\GalleryAlbum::create([
            'user_id' => $user->id,
            'title' => 'To Delete',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('admin.gallery.destroy', $album));

        $response->assertRedirect(route('admin.gallery.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('gallery_albums', [
            'id' => $album->id,
        ]);
    }

    public function test_public_can_view_gallery_index(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        \App\Models\GalleryAlbum::create([
            'user_id' => $user->id,
            'title' => 'Public Album',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $response = $this->get(route('public.gallery.index'));

        $response->assertStatus(200);
        $response->assertSee('Public Album');
    }

    public function test_public_can_view_published_public_album(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        $album = \App\Models\GalleryAlbum::create([
            'user_id' => $user->id,
            'title' => 'Visible Album',
            'visibility' => 'public',
            'is_published' => true,
        ]);

        $response = $this->get(route('public.gallery.show', $album));

        $response->assertStatus(200);
        $response->assertSee('Visible Album');
    }

    public function test_public_cannot_view_private_album(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $user->assignRole('admin');
        }

        $album = \App\Models\GalleryAlbum::create([
            'user_id' => $user->id,
            'title' => 'Private Album',
            'visibility' => 'private',
            'is_published' => true,
        ]);

        $response = $this->get(route('public.gallery.show', $album));

        $response->assertStatus(404);
    }
}
