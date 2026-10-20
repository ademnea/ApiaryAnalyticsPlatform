<?php

namespace Tests\Feature\Api\Farmer;

use App\Jobs\SendNotification;
use App\Mail\Farmer\FarmerPasswordReset;
use App\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * UC-FAPI-04.
 */
class PasswordResetTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_a_six_digit_code_for_a_known_farmer(): void
    {
        Mail::fake();

        $this->makeFarmer();

        $this->postJson('/api/v1/farmer/password/forgot', ['email' => 'farmer@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If this email is registered, you will receive a reset code shortly.');

        Mail::assertSent(FarmerPasswordReset::class, fn ($mail) => preg_match('/^[0-9]{6}$/', $mail->code) === 1);
        $reset = DB::table('password_reset_tokens')->where('email', 'farmer@example.com')->first();
        $this->assertNotNull($reset);
        $this->assertFalse(preg_match('/^[0-9]{6}$/', $reset->token) === 1);
    }

    public function test_forgot_password_does_not_resend_a_code_within_the_cooldown(): void
    {
        Mail::fake();
        $this->makeFarmer();

        $this->postJson('/api/v1/farmer/password/forgot', ['email' => 'farmer@example.com'])->assertOk();
        $this->postJson('/api/v1/farmer/password/forgot', ['email' => 'farmer@example.com'])->assertOk();

        Mail::assertSent(FarmerPasswordReset::class, 1);
    }

    public function test_forgot_password_sends_the_short_lived_reset_code_by_sms(): void
    {
        config([
            'services.africastalking.username' => 'sandbox',
            'services.africastalking.api_key' => 'test-key',
        ]);
        Http::fake([
            '*' => Http::response([
                'SMSMessageData' => [
                    'Recipients' => [['status' => 'Success']],
                ],
            ]),
        ]);
        Mail::fake();

        $this->makeFarmer();

        $this->postJson('/api/v1/farmer/password/forgot', ['email' => 'farmer@example.com'])
            ->assertOk();

        $smsLog = NotificationLog::where('type', 'password_reset')->where('channel', 'sms')->firstOrFail();
        SendNotification::dispatchSync($smsLog->id);

        $code = null;
        Mail::assertSent(FarmerPasswordReset::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->assertIsString($code);

        $this->assertDatabaseHas('notification_logs', [
            'recipient_type' => 'farmer',
            'recipient' => '256700000000',
            'type' => 'password_reset',
            'channel' => 'sms',
            'status' => 'sent',
        ]);
        $this->assertStringNotContainsString($code, $smsLog->content);
        $this->assertStringContainsString($code, Crypt::decryptString($smsLog->content));
        Http::assertSent(fn ($request) => str_contains($request['message'], $code)
            && str_contains($request['message'], 'expires in 10 minutes'));
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

    public function test_farmer_can_reset_password_with_a_valid_code(): void
    {
        [$user] = $this->makeFarmer();
        $code = '012345';
        $this->storeResetCode($user->email, $code);

        $this->postJson('/api/v1/farmer/password/reset', [
            'email' => 'farmer@example.com',
            'token' => $code,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password reset successfully. Please log in.');

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_reset_code_is_single_use(): void
    {
        [$user] = $this->makeFarmer();
        $code = '246810';
        $this->storeResetCode($user->email, $code);

        $payload = [
            'email' => 'farmer@example.com',
            'token' => $code,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ];

        $this->postJson('/api/v1/farmer/password/reset', $payload)->assertOk();
        $this->postJson('/api/v1/farmer/password/reset', $payload)->assertStatus(422);
    }

    public function test_expired_code_is_rejected(): void
    {
        [$user] = $this->makeFarmer();
        $code = '345678';
        $this->storeResetCode($user->email, $code);

        $this->travel(11)->minutes();

        $this->postJson('/api/v1/farmer/password/reset', [
            'email' => 'farmer@example.com',
            'token' => $code,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This password reset code is invalid, expired, or has too many attempts. Please request a new one.');
    }

    public function test_unknown_email_gets_the_same_message_as_a_bad_token(): void
    {
        $this->makeFarmer();

        $this->postJson('/api/v1/farmer/password/reset', [
            'email' => 'nobody@example.com',
            'token' => '999999',
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This password reset code is invalid, expired, or has too many attempts. Please request a new one.');
    }

    public function test_reset_code_attempts_are_limited_per_email(): void
    {
        [$user] = $this->makeFarmer();
        $code = '123456';
        $this->storeResetCode($user->email, $code);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/farmer/password/reset', [
                'email' => $user->email,
                'token' => '999999',
                'password' => 'brand-new-pass',
                'password_confirmation' => 'brand-new-pass',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/farmer/password/reset', [
            'email' => $user->email,
            'token' => $code,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertStatus(422);

        $this->assertFalse(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    /**
     * UC-FAPI-04 postcondition: a reset does not force a global logout.
     */
    public function test_existing_sessions_survive_a_password_reset(): void
    {
        [$user] = $this->makeFarmer();

        $token = $this->tokenFor($user);
        $resetCode = '987654';
        $this->storeResetCode($user->email, $resetCode);

        $this->postJson('/api/v1/farmer/password/reset', [
            'email' => 'farmer@example.com',
            'token' => $resetCode,
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->getJson('/api/v1/farmer/profile')
            ->assertOk();
    }

    private function storeResetCode(string $email, string $code): void
    {
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );
    }
}
