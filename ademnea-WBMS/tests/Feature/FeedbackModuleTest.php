<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\FeedbackCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_submit_feedback(): void
    {
        FeedbackCategory::create([
            'name' => 'General Inquiry',
            'description' => 'General questions',
        ]);

        $response = $this->post(route('public.feedback.store'), [
            'feedback_category_id' => 1,
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '123456789',
            'organization' => 'AdEMNEA',
            'subject' => 'Website feedback',
            'message' => 'The site looks great.',
        ]);

        $response->assertRedirect(route('public.feedback.success'));
        $this->assertDatabaseHas('feedback', [
            'email' => 'jane@example.com',
            'subject' => 'Website feedback',
            'status' => 'new',
        ]);
    }

    public function test_admin_can_view_and_update_feedback_status(): void
    {
        $user = User::factory()->create();
        $category = FeedbackCategory::create([
            'name' => 'Technical Issue',
            'description' => 'Technical problems',
        ]);
        $feedback = Feedback::create([
            'feedback_category_id' => $category->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Login issue',
            'message' => 'I cannot log in.',
            'status' => 'new',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.feedback.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.feedback.show', $feedback))
            ->assertOk();

        $this->actingAs($user)
            ->put(route('admin.feedback.update', $feedback), ['status' => 'resolved'])
            ->assertRedirect();

        $this->assertSame('resolved', $feedback->fresh()->status);
    }
}
