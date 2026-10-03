<?php

namespace Tests\Unit\Anomaly;

use App\Models\Alert;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use App\Models\User;
use App\Services\Anomaly\AnomalyAlertDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnomalyAlertDispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeDevice(bool $withFarmer = true, bool $onHive = true): IotDevice
    {
        $apiary = Apiary::factory()->create(['farmer_id' => $withFarmer ? Farmer::factory()->create()->id : null]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);

        return IotDevice::factory()->create([
            'hive_id' => $onHive ? $hive->id : null,
            'hardware_team_id' => IotHardwareTeam::factory()->create([
                'contact_email' => 'field-team@example.com',
                'contact_phone' => '+256700000001',
            ]),
        ]);
    }

    private function makeAdmin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeAnomaly(IotDevice $device, string $anomalyType = 'statistical_deviation'): SensorAnomaly
    {
        return SensorAnomaly::create([
            'device_id' => $device->id,
            'hive_id' => $device->hive_id,
            'sensor_type' => 'temperature',
            'anomaly_type' => $anomalyType,
            'anomaly_score' => 1.0,
            'record_value' => ['brood_section' => 45.0],
            'detection_layer' => 'rules',
            'detected_at' => now(),
        ]);
    }

    #[Test]
    public function a_data_anomaly_alerts_the_farmer_and_emails_the_admin(): void
    {
        $this->makeAdmin();
        $device = $this->makeDevice();
        $anomaly = $this->makeAnomaly($device, 'statistical_deviation');

        $this->assertTrue(app(AnomalyAlertDispatchService::class)->dispatch($anomaly));

        $alert = Alert::first();
        $this->assertSame($device->hive->apiary->farmer_id, $alert->farmer_id);
        $this->assertSame($anomaly->id, $alert->source_anomaly_id);
        $this->assertSame('data_anomaly', $alert->type);

        $this->assertDatabaseHas('notification_logs', ['recipient_type' => 'admin', 'recipient' => 'admin@example.com', 'channel' => 'email', 'status' => 'sent']);
        // No FCM token on the farmer: the push is on record as skipped.
        $this->assertDatabaseHas('notification_logs', ['recipient_type' => 'farmer', 'channel' => 'push', 'status' => 'skipped']);
        $this->assertDatabaseMissing('notification_logs', ['recipient_type' => 'hardware_team']);
        $this->assertTrue($anomaly->fresh()->alerted);
    }

    #[Test]
    public function a_sensor_fault_goes_to_the_admin_and_hardware_team_but_not_the_farmer(): void
    {
        $this->makeAdmin();
        $device = $this->makeDevice();

        app(AnomalyAlertDispatchService::class)->dispatch($this->makeAnomaly($device, 'frozen_sensor'));

        $this->assertDatabaseCount('alerts', 0);
        $this->assertDatabaseHas('notification_logs', ['recipient_type' => 'admin', 'channel' => 'email']);
        $this->assertDatabaseHas('notification_logs', ['recipient_type' => 'hardware_team', 'recipient' => 'field-team@example.com', 'channel' => 'email']);
        $this->assertDatabaseMissing('notification_logs', ['channel' => 'sms']);
    }

    #[Test]
    public function critical_battery_also_texts_the_hardware_team(): void
    {
        config(['services.africastalking.username' => 'sandbox', 'services.africastalking.api_key' => 'test-key']);
        Http::fake(['*' => Http::response(['SMSMessageData' => ['Recipients' => [['status' => 'Success']]]])]);

        $device = $this->makeDevice();

        app(AnomalyAlertDispatchService::class)->dispatch($this->makeAnomaly($device, 'critical_battery'));

        $this->assertDatabaseHas('notification_logs', [
            'recipient_type' => 'hardware_team',
            'recipient' => '+256700000001',
            'channel' => 'sms',
            'status' => 'sent',
        ]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sandbox.africastalking.com')
            && $request['to'] === '+256700000001');
    }

    #[Test]
    public function weak_signal_is_dashboard_only(): void
    {
        $this->makeAdmin();
        $device = $this->makeDevice();
        $anomaly = $this->makeAnomaly($device, 'weak_signal');

        app(AnomalyAlertDispatchService::class)->dispatch($anomaly);

        $this->assertDatabaseCount('notification_logs', 0);
        $this->assertDatabaseCount('alerts', 0);
        $this->assertTrue($anomaly->fresh()->alerted);
    }

    #[Test]
    public function a_device_with_no_hive_still_reaches_staff(): void
    {
        $this->makeAdmin();
        $device = $this->makeDevice(onHive: false);

        app(AnomalyAlertDispatchService::class)->dispatch($this->makeAnomaly($device, 'low_battery'));

        $this->assertDatabaseHas('notification_logs', ['recipient_type' => 'admin', 'channel' => 'email']);
        $this->assertDatabaseHas('notification_logs', ['recipient_type' => 'hardware_team', 'channel' => 'email']);
    }

    #[Test]
    public function a_farmer_route_with_no_farmer_creates_no_farmer_alert(): void
    {
        $device = $this->makeDevice(withFarmer: false);

        app(AnomalyAlertDispatchService::class)->dispatch($this->makeAnomaly($device, 'statistical_deviation'));

        $this->assertDatabaseCount('alerts', 0);
    }

    #[Test]
    public function the_same_anomaly_type_on_the_same_device_is_suppressed_within_the_cooldown(): void
    {
        $device = $this->makeDevice();
        $service = app(AnomalyAlertDispatchService::class);

        $this->assertTrue($service->dispatch($this->makeAnomaly($device)));
        $second = $this->makeAnomaly($device);
        $this->assertFalse($service->dispatch($second));

        $this->assertDatabaseCount('alerts', 1);
        $this->assertDatabaseCount('sensor_anomalies', 2); // both incidents persist regardless
        $this->assertFalse($second->fresh()->alerted);
    }

    #[Test]
    public function a_different_anomaly_type_on_the_same_device_is_not_suppressed(): void
    {
        $device = $this->makeDevice();
        $service = app(AnomalyAlertDispatchService::class);

        $service->dispatch($this->makeAnomaly($device, 'static_threshold_breach'));

        $this->assertTrue($service->dispatch($this->makeAnomaly($device, 'frozen_sensor')));
    }

    #[Test]
    public function critical_battery_has_a_short_cooldown_and_device_offline_has_none(): void
    {
        $device = $this->makeDevice();
        $service = app(AnomalyAlertDispatchService::class);

        $service->dispatch($this->makeAnomaly($device, 'critical_battery'));
        $this->assertFalse($service->dispatch($this->makeAnomaly($device, 'critical_battery')));

        // device_offline is gated by its open incident in CheckDeviceHealth instead.
        $this->assertTrue($service->dispatch($this->makeAnomaly($device, 'device_offline')));
        $this->assertTrue($service->dispatch($this->makeAnomaly($device, 'device_offline')));
    }

    #[Test]
    public function recovery_is_sent_only_for_an_incident_staff_were_told_about(): void
    {
        $this->makeAdmin();
        $device = $this->makeDevice();
        $service = app(AnomalyAlertDispatchService::class);

        $silent = $this->makeAnomaly($device, 'device_offline');
        $service->dispatchRecovery($silent);
        $this->assertDatabaseCount('notification_logs', 0);

        $alerted = $this->makeAnomaly($device, 'device_offline');
        $alerted->update(['alerted' => true, 'alerted_at' => now()]);
        $service->dispatchRecovery($alerted);

        $this->assertDatabaseHas('notification_logs', ['type' => 'device_recovered', 'recipient_type' => 'admin', 'channel' => 'email']);
        $this->assertDatabaseHas('notification_logs', ['type' => 'device_recovered', 'recipient_type' => 'hardware_team', 'channel' => 'email']);
    }
}
