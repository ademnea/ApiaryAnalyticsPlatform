<?php

namespace Tests\Feature\Api\Farmer;

use App\Mail\Farmer\FarmerPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * UC-FAPI-04.
 */
class PasswordResetTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_a_link_for_a_known_farmer(): void
    {
        Mail::fake();

        $this->makeFarmer();

        $this->postJson('/api/v1/farmer/password/forgot', ['email' => 'farmer@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If this email is registered, you will receive a reset link shortly.');

        Mail::assertSent(FarmerPasswordReset::class);
    }

    /**
     * The response must be byte-identical for an unknown address, or it is an
     * account-enumeration oracle.
     */
    public function test_forgot_password_answers_identically_for_an_unknown_address(): void
    {
        Mail::fake();

        $this->makeFarmer();

        $known = $this->postJson('/api/v1/farmer/password/forgot', ['email' => 'farmer@example.com']);
        $unknown = $this->postJson('/api/v1/farmer/password/forgot', ['email' => 'nobody@example.com']);

        $this->assertSame($known->status(), $unknown->status());
        $this->assertSame($known->getContent(), $unknown->getContent());

        Mail::assertNotSent(FarmerPasswordReset::class, fn ($mail) => $mail->hasTo('nobody@example.com'));
    }

    public function test_farmer_can_reset_password_with_a_valid_token(): void
    {
        [$user] = $this->makeFarmer();

        $token = Password::broker('users')->createToken($user);

        $this->postJson('/api/v1/farmer/password/reset', [
            'email'                 => 'farmer@example.com',
            'token'                 => $token,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password reset successfully. Please log in.');

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_reset_token_is_single_use(): void
    {
        [$user] = $this->makeFarmer();

        $token = Password::broker('users')->createToken($user);

        $payload = [
            'email'                 => 'farmer@example.com',
            'token'                 => $token,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ];

        $this->postJson('/api/v1/farmer/password/reset', $payload)->assertOk();
        $this->postJson('/api/v1/farmer/password/reset', $payload)->assertStatus(422);
    }

    public function test_expired_token_is_rejected(): void
    {
        [$user] = $this->makeFarmer();

        $token = Password::broker('users')->createToken($user);

        // config/auth.php sets the farmer-facing expiry to 60 minutes.
        $this->travel(61)->minutes();

        $this->postJson('/api/v1/farmer/password/reset', [
            'email'                 => 'farmer@example.com',
            'token'                 => $token,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This password reset link is invalid or has expired. Please request a new one.');
    }

    public function test_unknown_email_gets_the_same_message_as_a_bad_token(): void
    {
        $this->makeFarmer();

        $this->postJson('/api/v1/farmer/password/reset', [
            'email'                 => 'nobody@example.com',
            'token'                 => 'whatever',
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This password reset link is invalid or has expired. Please request a new one.');
    }

    /**
     * UC-FAPI-04 postcondition: a reset does not force a global logout.
     */
    public function test_existing_sessions_survive_a_password_reset(): void
    {
        [$user] = $this->makeFarmer();

        $token = $this->tokenFor($user);
        $resetToken = Password::broker('users')->createToken($user);

        $this->postJson('/api/v1/farmer/password/reset', [
            'email'                 => 'farmer@example.com',
            'token'                 => $resetToken,
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertOk();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('Accept', 'application/json')
            ->getJson('/api/v1/farmer/profile')
            ->assertOk();
    }
}
