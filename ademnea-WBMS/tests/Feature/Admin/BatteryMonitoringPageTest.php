<?php

namespace Tests\Feature\Admin;

use App\Models\IotDevice;
use App\Models\IotDeviceTelemetry;
use App\Models\IotDeviceTelemetryHistory;
use App\Models\IotHardwareTeam;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

class BatteryMonitoringPageTest extends TestCase
{
    use InteractsWithApiaryAdminAuth;
    use RefreshDatabase;

    #[Test]
    public function battery_page_lists_readings_without_exposing_device_mac_addresses(): void
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
        IotDeviceTelemetryHistory::create([
            'device_id' => $device->id,
            'battery_level' => 18.5,
            'battery_voltage' => 3.75,
            'battery_current' => 1.55,
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        $this->get(route('admin.devices.fleet', ['section' => 'battery']))
            ->assertOk()
            ->assertSee('Device Monitoring')
            ->assertSee('BATTERY-001')
            ->assertDontSee('30:76:F5:90:90:F0')
            ->assertSee('18.5%')
            ->assertSee('3.75 V')
            ->assertSee('1.55')
            ->assertSee('Voltage')
            ->assertSee('Current')
            ->assertSee('Battery readings')
            ->assertDontSee('Current battery status')
            ->assertDontSee('Fleet battery trend');
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

        $this->get(route('admin.devices.fleet', ['section' => 'overview']))
            ->assertOk()
            ->assertSee('1 stale')
            ->assertSee('Fleet battery trend · Last 7 days')
            ->assertSee('readings across 2 days')
            ->assertSee('batteryTrendChart');

        $this->get(route('admin.devices.fleet', ['section' => 'battery', 'battery' => 'stale']))
            ->assertOk()
            ->assertSee('Battery readings')
            ->assertDontSee('Fleet battery trend')
            ->assertDontSee('Current battery status');
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

        $this->get(route('admin.devices.fleet', ['section' => 'overview']))
            ->assertOk()
            ->assertSee('Not enough history for a trend yet.')
            ->assertDontSee('batteryTrendChart');
    }

    #[Test]
    public function battery_trend_uses_kampala_calendar_days_and_shows_local_timestamp(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00', 'Africa/Kampala'));
        $this->actingAsAdminWithPermission('view-device-fleet');

        $device = IotDevice::factory()->create([
            'device_code' => 'BATTERY-TZ',
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);
        IotDeviceTelemetry::create([
            'device_id' => $device->id,
            'battery_level' => 80,
            'last_heartbeat_at' => CarbonImmutable::parse('2026-10-22 09:34:56', 'UTC'),
            'battery_reading_at' => CarbonImmutable::parse('2026-10-22 09:34:56', 'UTC'),
        ]);

        foreach ([
            CarbonImmutable::parse('2026-10-20 20:30:00', 'UTC'),
            CarbonImmutable::parse('2026-10-20 21:30:00', 'UTC'),
        ] as $recordedAt) {
            IotDeviceTelemetryHistory::create([
                'device_id' => $device->id,
                'battery_level' => 80,
                'battery_voltage' => 3.8,
                'recorded_at' => $recordedAt,
                'created_at' => CarbonImmutable::parse('2026-10-22 09:34:56', 'UTC'),
            ]);
        }

        $this->get(route('admin.devices.fleet', ['section' => 'overview']))
            ->assertOk()
            ->assertViewHas('batteryTrendDays', 2)
            ->assertViewHas('batteryTrendReadingsCount', 2)
            ->assertViewHas('batteryTrendChart', fn (array $chart) => $chart['labels'][4] === 'Oct 20'
                && $chart['labels'][5] === 'Oct 21'
                && $chart['battery'][4] === 80.0
                && $chart['battery'][5] === 80.0);

        $this->get(route('admin.devices.fleet', ['section' => 'battery']))
            ->assertOk()
            ->assertSee('2026-10-20 23:30:00 EAT')
            ->assertSee('2026-10-21 00:30:00 EAT');
    }

    #[Test]
    public function battery_history_lists_reports_by_device_and_filters_by_local_time_range(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00', 'Africa/Kampala'));
        $this->actingAsAdminWithPermission('view-device-fleet');

        $selectedDevice = IotDevice::factory()->create([
            'device_code' => 'BATTERY-HISTORY-001',
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);
        $otherDevice = IotDevice::factory()->create([
            'device_code' => 'BATTERY-HISTORY-002',
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);

        foreach ([
            [$selectedDevice, CarbonImmutable::parse('2026-10-21 21:00:00', 'UTC'), 72.5],
            [$selectedDevice, CarbonImmutable::parse('2026-10-21 08:00:00', 'UTC'), 60.0],
            [$otherDevice, CarbonImmutable::parse('2026-10-22 10:00:00', 'UTC'), 90.0],
        ] as [$device, $recordedAt, $level]) {
            IotDeviceTelemetryHistory::create([
                'device_id' => $device->id,
                'battery_level' => $level,
                'battery_voltage' => 3.7,
                'recorded_at' => $recordedAt,
                'created_at' => CarbonImmutable::parse('2026-10-22 10:00:00', 'UTC'),
            ]);
        }

        $this->get(route('admin.devices.fleet', [
            'section' => 'battery',
            'battery_range' => '24h',
            'battery_device' => $selectedDevice->id,
        ]))
            ->assertOk()
            ->assertSee('BATTERY-HISTORY-001')
            ->assertSee('72.5%')
            ->assertDontSee('60.0%')
            ->assertViewHas('batteryHistory', fn ($history) => $history->total() === 1
                && $history->first()->device_id === $selectedDevice->id
                && $history->first()->recorded_at->format('Y-m-d H:i:s') === '2026-10-22 00:00:00');
    }

    #[Test]
    public function battery_history_is_paginated_and_keeps_its_filters(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');
        $device = IotDevice::factory()->create([
            'hardware_team_id' => IotHardwareTeam::factory(),
        ]);

        foreach (range(1, 26) as $reading) {
            IotDeviceTelemetryHistory::create([
                'device_id' => $device->id,
                'battery_level' => 70 + ($reading / 10),
                'recorded_at' => now()->subMinutes($reading),
                'created_at' => now()->subMinutes($reading),
            ]);
        }

        $this->get(route('admin.devices.fleet', [
            'section' => 'battery',
            'battery_range' => '30d',
            'battery_device' => $device->id,
        ]))
            ->assertOk()
            ->assertSee('battery_page=2')
            ->assertViewHas('batteryHistory', fn ($history) => $history->total() === 26
                && $history->count() === 25
                && str_contains($history->url(2), 'battery_range=30d')
                && str_contains($history->url(2), 'battery_device='.$device->id)
                && str_contains($history->url(2), 'section=battery'));
    }

    #[Test]
    public function device_monitoring_sections_present_focused_connectivity_and_issue_views(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');

        $this->get(route('admin.devices.fleet', ['section' => 'connectivity']))
            ->assertOk()
            ->assertSee('Device connectivity')
            ->assertSee('Connectivity readings')
            ->assertSee('Last contact')
            ->assertDontSee('Fleet battery trend');

        $this->get(route('admin.devices.fleet', ['section' => 'issues']))
            ->assertOk()
            ->assertSee('Latest open device issues')
            ->assertSee('View all issues')
            ->assertDontSee('Device connectivity');
    }

    #[Test]
    public function connectivity_readings_show_device_telemetry_without_mac_addresses(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');
        $device = IotDevice::factory()->create([
            'device_code' => 'CONNECTIVITY-001',
            'hardware_team_id' => IotHardwareTeam::factory(),
            'mac_address' => '30:76:F5:90:90:F0',
        ]);
        IotDeviceTelemetryHistory::create([
            'device_id' => $device->id,
            'signal_strength' => -68,
            'uptime_seconds' => 3600,
            'sensor_read_success_rate' => 0.98,
            'reboot_count' => 2,
            'cpu_usage' => 25,
            'storage_usage' => 40,
            'error_codes' => ['SENSOR_TIMEOUT'],
            'recorded_at' => now(),
            'created_at' => now(),
        ]);

        $this->get(route('admin.devices.fleet', ['section' => 'connectivity']))
            ->assertOk()
            ->assertSee('CONNECTIVITY-001')
            ->assertSee('-68 dBm')
            ->assertSee('3,600 s')
            ->assertSee('98.0%')
            ->assertSee('SENSOR_TIMEOUT')
            ->assertDontSee('30:76:F5:90:90:F0');
    }

    #[Test]
    public function battery_and_connectivity_readings_can_be_exported_as_csv(): void
    {
        $this->actingAsAdminWithPermission('view-device-fleet');

        foreach (['battery', 'connectivity'] as $stream) {
            $response = $this->get(route('admin.devices.fleet.readings.export', [
                'stream' => $stream,
                "{$stream}_range" => '30d',
            ]));

            $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $this->assertStringContainsString(
                "{$stream}-readings-",
                $response->headers->get('content-disposition'),
            );
        }
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
