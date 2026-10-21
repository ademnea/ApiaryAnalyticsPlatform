<?php

namespace Tests\Feature\Admin;

use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\IotHardwareTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class BatteryMonitoringPageTest extends TestCase
{
    use InteractsWithApiaryAdminAuth;
    use RefreshDatabase;

    #[Test]
    public function a_fleet_viewer_can_monitor_device_health_and_battery_from_one_page(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');
        $device = IotDevice::factory()->create([
            'device_code' => 'BATTERY-001',
            'hardware_team_id' => IotHardwareTeam::factory(),
            'mac_address' => '30:76:F5:90:90:F0',
            'chip' => 'ESP32',
        ]);
        IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'battery_level' => 18.5,
            'battery_voltage' => 3.75,
            'battery_current' => 1.55,
            'last_heartbeat_at' => now(),
            'battery_reading_at' => now(),
        ]);

        $this->get(route('admin.devices.fleet', ['section' => 'battery']))
            ->assertOk()
            ->assertSee('Device Monitoring')
            ->assertSee('BATTERY-001')
            ->assertSee('30:76:F5:90:90:F0')
            ->assertSee('18.5%')
            ->assertSee('3.75 V')
            ->assertSee('1.55')
            ->assertSee('Voltage')
            ->assertSee('Current')
            ->assertSee('Fresh')
            ->assertSee('Low');
    }

    #[Test]
    public function it_marks_old_battery_readings_stale_and_shows_a_trend_when_history_spans_multiple_days(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');
        $device = IotDevice::factory()->create([
            'device_code' => 'BATTERY-STALE',
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);
        IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'battery_level' => 42,
            'battery_voltage' => 3.6,
            'last_heartbeat_at' => now()->subHours(3),
            'battery_reading_at' => now()->subHours(3),
        ]);

        foreach ([now()->subDays(2), now()->subDay()] as $day => $recordedAt) {
            IotDeviceTelemetryHistory::create([
                'device_id' => $device->id,
                'battery_level' => 80 - ($day * 10),
                'battery_voltage' => 3.9 - ($day * 0.1),
                'recorded_at' => $recordedAt,
                'created_at' => now(),
            ]);
        }

        $this->get(route('admin.devices.fleet', ['section' => 'battery']))
            ->assertOk()
            ->assertSee('Stale readings')
            ->assertSee('Stale')
            ->assertSee('Fleet battery trend · Last 7 days')
            ->assertSee('readings across 2 days')
            ->assertSee('batteryTrendChart');

        $this->get(route('admin.devices.fleet', ['section' => 'battery', 'battery' => 'stale']))
            ->assertOk()
            ->assertSee('BATTERY-STALE')
            ->assertSee('Stale reading');
    }

    #[Test]
    public function it_does_not_claim_a_trend_when_history_only_covers_one_day(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');
        $device = IotDevice::factory()->create([
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);
        IotDeviceTelemetryHistory::create([
            'device_id' => $device->id,
            'battery_level' => 80,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        $this->get(route('admin.devices.fleet', ['section' => 'battery']))
            ->assertOk()
            ->assertSee('Not enough history for a trend yet.')
            ->assertDontSee('batteryTrendChart');
    }

    #[Test]
    public function device_monitoring_sections_present_focused_connectivity_and_issue_views(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');

        $this->get(route('admin.devices.fleet', ['section' => 'connectivity']))
            ->assertOk()
            ->assertSee('Device connectivity')
            ->assertSee('Last contact')
            ->assertDontSee('Fleet battery trend');

        $this->get(route('admin.devices.fleet', ['section' => 'issues']))
            ->assertOk()
            ->assertSee('Latest open device issues')
            ->assertSee('View all issues')
            ->assertDontSee('Device connectivity');
    }

    #[Test]
    public function the_old_battery_monitor_url_redirects_to_unified_device_monitoring(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');

        $this->get(route('admin.devices.battery'))
            ->assertRedirect(route('admin.devices.fleet'));
    }

    #[Test]
    public function the_battery_monitor_requires_device_fleet_or_iot_device_permission(): void
    {
        $this->actingAsAdminWithPermission('manage-hives');

        $this->get(route('admin.devices.battery'))->assertForbidden();
    }
}
