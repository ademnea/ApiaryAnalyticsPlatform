<?php

namespace Tests\Feature\Admin;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveTemperature;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\NotificationLog;
use App\Models\SensorAnomaly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

/**
 * The incident page explains what the rule saw, charts the readings around
 * it, and lists who was notified.
 */
class AnomalyEvidenceTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithApiaryAdminAuth;

    private IotDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $apiary = Apiary::factory()->create(['farmer_id' => Farmer::factory()->create()->id]);
        $hive = Hive::factory()->create(['apiary_id' => $apiary->id]);
        $this->device = IotDevice::factory()->create(['hive_id' => $hive->id, 'hardware_team_id' => IotHardwareTeam::factory()]);

        $this->actingAsAdminWithPermission('view-anomaly-analytics');
    }

    private function incident(string $sensorType, string $anomalyType, array $recordValue, ?Carbon $detectedAt = null): SensorAnomaly
    {
        return SensorAnomaly::recordOrTouch([
            'device_id' => $this->device->id,
            'hive_id' => $this->device->hive_id,
            'sensor_type' => $sensorType,
            'anomaly_type' => $anomalyType,
            'anomaly_score' => 1.0,
            'record_value' => $recordValue,
            'detection_layer' => 'rules',
            'detected_at' => $detectedAt ?? now(),
        ]);
    }

    private function temperature(Carbon $recordedAt, float $brood, bool $suspect = false): void
    {
        HiveTemperature::create([
            'hive_id' => $this->device->hive_id,
            'device_id' => $this->device->id,
            'honey_section' => 30,
            'brood_section' => $brood,
            'exterior' => 25,
            'suspect' => $suspect,
            'recorded_at' => $recordedAt,
            'created_at' => now(),
        ]);
    }

    #[Test]
    public function an_impossible_reading_is_explained_and_charted_against_the_plausible_range(): void
    {
        $detectedAt = now()->subHour()->startOfMinute();
        $this->temperature($detectedAt->copy()->subHours(2), 34.5);
        $this->temperature($detectedAt, 71.2, suspect: true);
        $this->temperature($detectedAt->copy()->subDays(3), 20); // outside the charted window

        $anomaly = $this->incident('temperature', 'static_threshold_breach', ['brood_section' => 71.2], $detectedAt);

        $this->get(route('admin.anomaly.anomalies.show', $anomaly))
            ->assertOk()
            ->assertSee('Brood Chamber temperature read 71.2 °C. The plausible range is -10.0 °C to 60.0 °C')
            ->assertSee('The value is physically impossible. Inspect the sensor.')
            ->assertViewHas('chart', fn (?array $chart) => $chart['values'] === [34.5, 71.2]
                && $chart['flagged'] === [false, true]
                && $chart['band'] === ['label' => 'Plausible range', 'min' => -10.0, 'max' => 60.0]);
    }

    #[Test]
    public function a_statistical_deviation_is_explained_against_the_hives_own_baseline(): void
    {
        $detectedAt = now()->subHour()->startOfMinute();
        $this->temperature($detectedAt, 45);

        $anomaly = $this->incident('temperature', 'statistical_deviation', ['brood_section' => 45, 'mean' => 34.2, 'stddev' => 0.8], $detectedAt);

        $this->get(route('admin.anomaly.anomalies.show', $anomaly))
            ->assertOk()
            ->assertSee('this hive averaged 34.2 °C with a standard deviation of 0.8 °C, which puts this reading 13.5 standard deviations away. The limit is 3.')
            ->assertViewHas('chart', fn (?array $chart) => $chart['flagged'] === [true]
                && round($chart['band']['min'], 1) === 31.8
                && round($chart['band']['max'], 1) === 36.6);
    }

    #[Test]
    public function a_device_issue_is_explained_without_a_readings_chart(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');
        $anomaly = $this->incident('telemetry', 'low_battery', ['battery_level' => 15]);

        $this->get(route('admin.anomaly.anomalies.show', $anomaly))
            ->assertOk()
            ->assertSee('Battery was at 15 %, at or below the low-battery limit of 20 %.')
            ->assertViewHas('chart', null);
    }

    #[Test]
    public function staff_notifications_are_listed_with_their_delivery_status(): void
    {
        $anomaly = $this->incident('temperature', 'frozen_sensor', ['brood_section' => 34.5]);
        $anomaly->update(['alerted' => true, 'alerted_at' => now()]);

        NotificationLog::create([
            'recipient_type' => 'hardware_team',
            'recipient' => 'team@example.test',
            'sensor_anomaly_id' => $anomaly->id,
            'type' => 'frozen_sensor',
            'channel' => 'email',
            'subject' => '[WARNING] Sensor stuck',
            'content' => 'body',
            'status' => 'failed',
            'error_message' => 'Connection refused',
        ]);

        $this->get(route('admin.anomaly.anomalies.show', $anomaly))
            ->assertOk()
            ->assertSee('team@example.test')
            ->assertSee('Connection refused')
            ->assertDontSee('No farmer alert was sent')
            ->assertViewHas('notificationSummary', fn (array $summary) => $summary['emptyReason'] === null
                && $summary['routes'] === [
                    ['recipient' => 'Admins', 'channels' => ['email']],
                    ['recipient' => 'Hardware team', 'channels' => ['email']],
                ]);
    }

    #[Test]
    public function the_reason_is_given_when_nobody_was_notified(): void
    {
        $suppressed = $this->incident('temperature', 'frozen_sensor', ['brood_section' => 34.5]);
        $dashboardOnly = $this->incident('telemetry', 'weak_signal', ['signal_strength' => -95, 'weak_for_minutes' => 40]);

        $this->get(route('admin.anomaly.anomalies.show', $suppressed))
            ->assertOk()
            ->assertSee('Most likely the cooldown suppressed it')
            ->assertSee('within the previous 60 minutes');

        $this->get(route('admin.anomaly.anomalies.show', $dashboardOnly))
            ->assertOk()
            ->assertSee('This type is shown on the dashboard only, so nobody is notified.')
            ->assertSee('Signal strength was -95 dBm, at or below the weak-signal limit of -85 dBm, and had been weak for 40 minutes.');
    }
}
