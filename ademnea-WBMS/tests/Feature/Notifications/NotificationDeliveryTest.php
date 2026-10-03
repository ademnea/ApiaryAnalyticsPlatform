<?php

namespace Tests\Feature\Notifications;

use App\Jobs\SendNotification;
use App\Models\Alert;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\NotificationLog;
use App\Services\Farmer\NotificationDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function makeAlert(?string $fcmToken): Alert
    {
        $farmer = Farmer::factory()->create(['fcm_token' => $fcmToken]);
        $apiary = Apiary::factory()->create(['farmer_id' => $farmer->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);

        return Alert::create([
            'farmer_id' => $farmer->id,
            'hive_id' => $hive->id,
            'type' => 'data_anomaly',
            'message' => 'Statistical Deviation on hive H-1: Brood Section: 45.',
            'is_read' => false,
            'created_at' => now(),
        ]);
    }

    private function configureFcm(): void
    {
        config(['services.fcm.project_id' => 'ademnea-test', 'services.fcm.access_token' => 'test-token']);
    }

    #[Test]
    public function a_farmer_alert_is_pushed_to_their_phone(): void
    {
        $this->configureFcm();
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/ademnea-test/messages/1'])]);

        $log = app(NotificationDispatchService::class)->dispatch($this->makeAlert('farmer-device-token'));

        $this->assertSame('sent', $log->status);
        $this->assertSame(1, $log->attempts);
        Http::assertSent(fn ($request) => $request->url() === 'https://fcm.googleapis.com/v1/projects/ademnea-test/messages:send'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['message']['token'] === 'farmer-device-token');
    }

    #[Test]
    public function a_farmer_without_a_token_is_logged_as_skipped(): void
    {
        $this->configureFcm();
        Http::fake();

        $log = app(NotificationDispatchService::class)->dispatch($this->makeAlert(null));

        $this->assertSame('skipped', $log->status);
        Http::assertNothingSent();
    }

    #[Test]
    public function push_is_skipped_when_fcm_is_not_configured(): void
    {
        Http::fake();

        $log = app(NotificationDispatchService::class)->dispatch($this->makeAlert('farmer-device-token'));

        $this->assertSame('skipped', $log->status);
        Http::assertNothingSent();
    }

    #[Test]
    public function a_rejected_push_throws_so_the_queue_retries_and_the_final_failure_is_recorded(): void
    {
        $this->configureFcm();
        Http::fake(['fcm.googleapis.com/*' => Http::response(['error' => 'UNREGISTERED'], 404)]);

        $log = NotificationLog::create([
            'recipient_type' => 'farmer',
            'recipient' => 'stale-token',
            'type' => 'data_anomaly',
            'channel' => 'push',
            'subject' => 'AdEMNEA hive alert',
            'content' => 'Test',
            'status' => 'pending',
        ]);
        $job = new SendNotification($log->id);

        try {
            app()->call([$job, 'handle']);
            $this->fail('A rejected push must throw so the queue retries it.');
        } catch (RuntimeException) {
            $job->failed(new RuntimeException('FCM push failed (404)'));
        }

        $this->assertSame(3, $job->tries);
        $this->assertSame('failed', $log->fresh()->status);
        $this->assertStringContainsString('404', $log->fresh()->error_message);
    }
}
