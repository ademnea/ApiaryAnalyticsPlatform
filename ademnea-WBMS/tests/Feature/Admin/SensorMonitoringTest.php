<?php

namespace Tests\Feature\Admin;

use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\HiveHumidity;
use App\Models\HivePhoto;
use App\Models\HiveTemperature;
use App\Models\HiveWeight;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Models\SensorAnomaly;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Admin\Concerns\InteractsWithApiaryAdminAuth;
use Tests\TestCase;

/**
 * Sensor Monitoring pages, filters, CSV export and auto-refresh fragments.
 *
 * Uses RefreshDatabase. With the current phpunit.xml that is the database
 * named in .env, so point tests at a separate database before running these.
 */
class SensorMonitoringTest extends TestCase
{
    use InteractsWithApiaryAdminAuth;
    use RefreshDatabase;

    private Apiary $apiary;

    private Hive $hive;

    private IotDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-11 12:00:00'));

        $this->apiary = Apiary::factory()->create(['farmer_id' => Farmer::factory()->create()->id]);
        $this->hive = Hive::factory()->create(['apiary_id' => $this->apiary->id]);
        $this->device = $this->deviceFor($this->hive);
    }

    public static function pages(): array
    {
        return collect(['index', 'temperature', 'humidity', 'weight', 'co2', 'insights', 'photos', 'audio', 'video'])
            ->mapWithKeys(fn (string $page) => [$page => ["admin.monitoring.{$page}"]])
            ->all();
    }

    // ---- Access and rendering -----------------------------------------------

    #[Test]
    #[DataProvider('pages')]
    public function guests_cannot_open_monitoring_pages(string $route): void
    {
        $this->assertContains($this->get(route($route))->status(), [302, 403]);
    }

    #[Test]
    #[DataProvider('pages')]
    public function users_without_a_monitoring_permission_are_forbidden(string $route): void
    {
        Permission::findOrCreate('view-monitoring-dashboard', 'web');
        Permission::findOrCreate('view-hive-data', 'web');

        $this->actingAs(User::factory()->create())->get(route($route))->assertForbidden();
    }

    #[Test]
    #[DataProvider('pages')]
    public function every_page_renders_with_no_data(string $route): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->get(route($route))->assertOk();
    }

    #[Test]
    #[DataProvider('pages')]
    public function a_refresh_poll_gets_only_the_live_fragment(string $route): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $page = $this->get(route($route))->assertOk()
            ->assertSee('data-monitoring-live', false)
            ->assertSee('data-monitoring-refresh', false);
        $this->assertStringContainsString('X-Monitoring-Refresh', (string) $page->headers->get('Vary'));

        $fragment = $this->get(route($route), ['X-Monitoring-Refresh' => '1'])->assertOk()
            ->assertSee('data-monitoring-live', false)
            ->assertDontSee('<html', false)
            ->assertDontSee('role="search"', false)
            ->assertDontSee('data-monitoring-refresh', false);
        $this->assertStringContainsString('no-store', (string) $fragment->headers->get('Cache-Control'));
    }

    #[Test]
    public function the_hive_data_permission_alone_is_enough(): void
    {
        $this->actingAsAdminWithPermission('view-hive-data');

        $this->get(route('admin.monitoring.temperature'))->assertOk();
    }

    // ---- Sensor pages -------------------------------------------------------

    #[Test]
    public function the_temperature_page_summarises_charts_and_lists_readings(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->recordTemperature(['honey_section' => 33.0, 'brood_section' => 34.5, 'exterior' => 21.0], now()->subHours(3));
        $this->recordTemperature(['honey_section' => 33.4, 'brood_section' => 35.5, 'exterior' => 22.0], now()->subHours(2));
        $this->recordTemperature(['honey_section' => 32.8, 'brood_section' => 35.0, 'exterior' => null], now()->subMinutes(10));

        $this->get(route('admin.monitoring.temperature'))
            ->assertOk()
            ->assertSee('Brood Chamber')
            ->assertSee('Live')
            ->assertSee(route('admin.monitoring.export', ['metric' => 'temperature']), false)
            ->assertViewHas('summary', fn (array $summary) => $summary['readings'] === 3
                && $summary['hives_reporting'] === 1
                && abs($summary['columns']['brood_section']['max'] - 35.5) < 0.001
                && abs($summary['columns']['brood_section']['latest'] - 35.0) < 0.001
                && $summary['columns']['exterior']['latest'] === null)
            ->assertViewHas('chart', function (array $chart) {
                $points = count($chart['labels']);

                // Seven days, hourly, gap-filled: every hour has a slot and
                // only the three hours with readings hold values.
                return $chart['has_data']
                    && $points >= 168
                    && collect($chart['series'])->every(fn (array $s) => count($s['avg']) === $points)
                    && count(array_filter($chart['series'][0]['avg'], fn ($v) => $v !== null)) === 3;
            })
            ->assertViewHas('readings', fn ($readings) => $readings->total() === 3);
    }

    #[Test]
    public function readings_outside_the_window_or_the_chosen_hive_are_excluded(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $otherHive = Hive::factory()->create(['apiary_id' => $this->apiary->id]);
        $this->recordTemperature(['brood_section' => 34.0], now()->subHour());
        $this->recordTemperature(['brood_section' => 34.0], now()->subDays(10));
        $this->recordTemperature(['brood_section' => 30.0], now()->subHour(), $otherHive);

        $this->get(route('admin.monitoring.temperature', ['hive_id' => $this->hive->id]))
            ->assertViewHas('summary', fn (array $summary) => $summary['readings'] === 1);

        $this->get(route('admin.monitoring.temperature', ['range' => '30d']))
            ->assertViewHas('summary', fn (array $summary) => $summary['readings'] === 3 && $summary['hives_reporting'] === 2);
    }

    #[Test]
    public function the_apiary_filter_scopes_to_its_hives_and_a_hive_overrides_it(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $otherApiary = Apiary::factory()->create(['farmer_id' => Farmer::factory()->create()->id]);
        $otherHive = Hive::factory()->create(['apiary_id' => $otherApiary->id]);
        $this->recordTemperature(['brood_section' => 34.0], now()->subHour());
        $this->recordTemperature(['brood_section' => 34.0], now()->subHour(), $otherHive);

        $this->get(route('admin.monitoring.temperature', ['apiary_id' => $otherApiary->id]))
            ->assertViewHas('summary', fn (array $summary) => $summary['readings'] === 1);

        $this->get(route('admin.monitoring.temperature', ['apiary_id' => $otherApiary->id, 'hive_id' => $this->hive->id]))
            ->assertViewHas('filters', fn ($filters) => $filters->apiaryId === null && $filters->hiveId === $this->hive->id)
            ->assertViewHas('summary', fn (array $summary) => $summary['readings'] === 1);
    }

    #[Test]
    public function the_flagged_filter_shows_only_suspect_readings(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->recordTemperature(['brood_section' => 34.0], now()->subHours(2));
        $this->recordTemperature(['brood_section' => 99.0], now()->subHour(), suspect: true);

        $this->get(route('admin.monitoring.temperature'))
            ->assertViewHas('summary', fn (array $summary) => $summary['readings'] === 2 && $summary['flagged'] === 1);

        $this->get(route('admin.monitoring.temperature', ['flagged' => 1]))
            ->assertViewHas('summary', fn (array $summary) => $summary['readings'] === 1);
    }

    #[Test]
    public function only_open_anomalies_for_the_page_metric_are_counted(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        foreach ([['temperature', false], ['temperature', true], ['humidity', false]] as [$sensorType, $resolved]) {
            SensorAnomaly::create([
                'device_id' => $this->device->id,
                'hive_id' => $this->hive->id,
                'sensor_type' => $sensorType,
                'anomaly_type' => 'static_threshold_breach',
                'anomaly_score' => 1.0,
                'record_value' => ['brood_section' => 99],
                'detection_layer' => 'rules',
                'detected_at' => now()->subHour(),
                'resolved' => $resolved,
            ]);
        }

        $this->get(route('admin.monitoring.temperature'))->assertViewHas('anomalyCount', 1);
        $this->get(route('admin.monitoring.weight'))->assertViewHas('anomalyCount', 0);
        $this->get(route('admin.monitoring.index'))->assertViewHas('anomalyCount', 2);
    }

    #[Test]
    public function a_reading_older_than_the_offline_threshold_is_shown_as_stale(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        HiveHumidity::create([
            'hive_id' => $this->hive->id,
            'device_id' => $this->device->id,
            'brood_section' => 60.0,
            'suspect' => false,
            'recorded_at' => now()->subHours(5),
            'created_at' => now()->subHours(5),
        ]);

        $this->get(route('admin.monitoring.humidity'))->assertOk()->assertSee('Stale')->assertDontSee('Catching up');
    }

    #[Test]
    public function an_old_reading_that_arrived_just_now_is_shown_as_catching_up(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->recordTemperature(['brood_section' => 34.0], now()->subDays(3)->subHour(), receivedAt: now()->subMinute());

        $this->get(route('admin.monitoring.temperature'))
            ->assertOk()
            ->assertSee('Catching up')
            ->assertSee('3 days late')
            ->assertViewHas('summary', fn (array $summary) => $summary['last_received_at']->equalTo(now()->subMinute()));
    }

    // ---- Readings table -----------------------------------------------------

    #[Test]
    public function readings_can_be_listed_by_when_the_server_received_them(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $onTime = $this->recordTemperature(['brood_section' => 34.0], now()->subHour(), receivedAt: now()->subHour());
        $backlog = $this->recordTemperature(['brood_section' => 35.0], now()->subDays(3), receivedAt: now());

        $this->get(route('admin.monitoring.temperature'))
            ->assertViewHas('readings', fn ($readings) => $readings->first()->is($onTime));

        $this->get(route('admin.monitoring.temperature', ['sort' => 'received']))
            ->assertViewHas('readings', fn ($readings) => $readings->first()->is($backlog));
    }

    #[Test]
    public function paging_pins_the_table_and_announces_newer_readings(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        foreach (range(1, 11) as $minutes) {
            $last = $this->recordTemperature(['brood_section' => 34.0], now()->subMinutes($minutes * 10));
        }

        $this->get(route('admin.monitoring.temperature', ['per_page' => 10]))
            ->assertSee('class="pagination"', false)
            ->assertViewHas('readings', fn ($readings) => str_contains($readings->url(2), 'upto='.$last->id)
                && str_contains($readings->url(2), 'per_page=10')
                && str_ends_with($readings->url(2), '#readings'));

        $this->recordTemperature(['brood_section' => 35.0], now()->subMinute());

        $this->get(route('admin.monitoring.temperature', ['per_page' => 10, 'page' => 2, 'upto' => $last->id]))
            ->assertViewHas('readings', fn ($readings) => $readings->total() === 11 && $readings->count() === 1)
            ->assertViewHas('newSinceSnapshot', 1)
            ->assertSee('Show latest')
            ->assertViewHas('summary', fn (array $summary) => $summary['readings'] === 12);
    }

    #[Test]
    public function the_latest_panel_has_one_row_per_device_with_its_newest_values(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->recordTemperature(['brood_section' => 34.0], now()->subHours(2));
        $this->recordTemperature(['brood_section' => 35.5], now()->subHour());
        $this->recordTemperature(['brood_section' => 30.0], now()->subHour(), Hive::factory()->create(['apiary_id' => $this->apiary->id]));

        $this->get(route('admin.monitoring.temperature'))
            ->assertSee('Latest by Device')
            ->assertViewHas('latestByDevice', function ($rows) {
                $mine = $rows->firstWhere('device_id', $this->device->id);

                return $rows->count() === 2
                    && abs($mine->brood_section - 35.5) < 0.001
                    && $mine->readings_count === 2;
            });
    }

    #[Test]
    public function the_weight_page_charts_each_hives_day_over_day_change(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        foreach ([[now()->subDays(2)->setTime(9, 0), 40.0], [now()->subDays(2)->setTime(18, 0), 42.0], [now()->subDay()->setTime(9, 0), 44.0]] as [$at, $kg]) {
            HiveWeight::create([
                'hive_id' => $this->hive->id,
                'device_id' => $this->device->id,
                'weight_kg' => $kg,
                'suspect' => false,
                'recorded_at' => $at,
                'created_at' => $at,
            ]);
        }

        $this->get(route('admin.monitoring.weight'))
            ->assertOk()
            ->assertViewHas('weightChange', function (array $change) {
                $values = array_values(array_filter($change['values'], fn ($v) => $v !== null));

                // Day average 41 kg, then 44 kg.
                return $change['has_data'] && $values === [3.0];
            });

        $this->get(route('admin.monitoring.temperature'))->assertViewHas('weightChange', null);
    }

    #[Test]
    public function bad_table_preferences_are_validation_errors(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->get(route('admin.monitoring.temperature', ['per_page' => 7]))->assertSessionHasErrors('per_page');
        $this->get(route('admin.monitoring.temperature', ['sort' => 'hive']))->assertSessionHasErrors('sort');
        $this->get(route('admin.monitoring.temperature', ['upto' => 'abc']))->assertSessionHasErrors('upto');
    }

    // ---- Insights -----------------------------------------------------------

    #[Test]
    public function insights_ask_for_a_hive_before_querying_anything(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->get(route('admin.monitoring.insights'))
            ->assertOk()
            ->assertSee('Choose a hive')
            ->assertViewHas('insights', null);
    }

    #[Test]
    public function insights_pair_the_streams_of_the_chosen_hive(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->recordTemperature(['brood_section' => 34.5, 'exterior' => 18.0], now()->subHour());

        $this->get(route('admin.monitoring.insights', ['hive_id' => $this->hive->id]))
            ->assertOk()
            ->assertSee('Thermoregulation')
            ->assertViewHas('insights', fn (array $insights) => array_keys($insights['charts']) === ['thermoregulation', 'climate', 'ventilation', 'foraging']
                && $insights['charts']['thermoregulation']['has_data']
                && array_filter($insights['charts']['foraging']['datasets'][0]['data']) === [] // no weight readings
                && count($insights['charts']['thermoregulation']['bands']) === 1
                && count($insights['charts']['climate']['datasets'][0]['data']) === count($insights['labels']));
    }

    // ---- Validation ---------------------------------------------------------

    #[Test]
    public function bad_windows_are_validation_errors(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');
        $page = route('admin.monitoring.temperature');

        $this->get($page.'?from=2026-01-01&to=2026-09-10')->assertSessionHasErrors('to'); // longer than 90 days
        $this->get($page.'?from=2026-09-01&to=2026-12-01')->assertSessionHasErrors('to'); // future
        $this->get($page.'?from=2026-09-05&to=2026-09-01')->assertSessionHasErrors('to'); // inverted
        $this->get($page.'?from=not-a-date&to=2026-09-05')->assertSessionHasErrors('from');
    }

    #[Test]
    public function typed_dates_win_over_the_preset_and_an_end_date_alone_is_accepted(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->get(route('admin.monitoring.temperature', ['range' => '30d', 'from' => '2026-09-01', 'to' => '2026-09-05']))
            ->assertOk()
            ->assertViewHas('filters', fn ($filters) => $filters->isCustom() && $filters->from->toDateString() === '2026-09-01');

        $this->get(route('admin.monitoring.temperature', ['to' => '2026-09-05']))
            ->assertOk()
            ->assertSessionHasNoErrors()
            ->assertViewHas('filters', fn ($filters) => $filters->from->toDateString() === '2026-08-29');
    }

    // ---- Export -------------------------------------------------------------

    #[Test]
    public function the_csv_export_streams_the_filtered_readings(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->recordTemperature(['honey_section' => 33.0, 'brood_section' => 34.5, 'exterior' => 21.0], now()->subHours(2));
        $this->recordTemperature(['honey_section' => 33.4, 'brood_section' => 35.5, 'exterior' => 22.0], now()->subHour());
        $this->recordTemperature(['brood_section' => 40.0], now()->subDays(20));

        $response = $this->get(route('admin.monitoring.export', ['metric' => 'temperature', 'range' => '7d']))->assertOk();
        $lines = array_values(array_filter(explode("\n", $response->streamedContent())));

        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertCount(3, $lines, 'Header plus the two in-window readings.');
        $this->assertStringContainsString('Honey Super (°C)', $lines[0]);
        $this->assertStringContainsString('Received At (UTC)', $lines[0]);
        $this->assertStringContainsString('34.5', $lines[1]);
        $this->assertStringContainsString($this->device->device_code, $lines[1]);
    }

    #[Test]
    public function exporting_anything_but_a_sensor_stream_is_not_found(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $this->get('/admin/monitoring/pressure/export')->assertNotFound();
        $this->get('/admin/monitoring/photos/export')->assertNotFound();
    }

    // ---- Overview and media -------------------------------------------------

    #[Test]
    public function the_overview_reports_per_hive_stream_coverage(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        $silentHive = Hive::factory()->create(['apiary_id' => $this->apiary->id]);
        $this->recordTemperature(['brood_section' => 34.0], now()->subMinutes(5));

        $this->get(route('admin.monitoring.index'))
            ->assertOk()
            ->assertSee('Silent')
            ->assertViewHas('coverage', fn ($coverage) => $coverage['temperature']->has($this->hive->id)
                && ! $coverage['weight']->has($this->hive->id)
                && ! $coverage['temperature']->has($silentHive->id))
            ->assertViewHas('sensorSnapshot', fn (array $snapshot) => $snapshot['temperature']['readings'] === 1
                && count(array_filter($snapshot['temperature']['spark'], fn ($v) => $v !== null)) === 1)
            ->assertViewHas('volume', fn (array $volume) => $volume['has_data']
                && array_sum($volume['series'][0]['counts']) === 1
                && array_sum($volume['series'][3]['counts']) === 0);
    }

    #[Test]
    public function the_photos_page_lists_captures_in_the_window(): void
    {
        $this->actingAsAdminWithPermission('view-monitoring-dashboard');

        foreach ([now()->subHour(), now()->subDays(30)] as $i => $capturedAt) {
            HivePhoto::create([
                'hive_id' => $this->hive->id,
                'device_id' => $this->device->id,
                'file_path' => "hives/{$this->hive->id}/photo-{$i}.jpg",
                'file_size_bytes' => 204800,
                'recorded_at' => $capturedAt,
                'created_at' => now(),
            ]);
        }

        $this->get(route('admin.monitoring.photos'))
            ->assertOk()
            ->assertSee('photo-0.jpg', false)
            ->assertDontSee('photo-1.jpg', false)
            ->assertViewHas('summary', fn (array $summary) => $summary['files'] === 1 && $summary['total_bytes'] === 204800);
    }

    // ---- Helpers ------------------------------------------------------------

    private function deviceFor(Hive $hive): IotDevice
    {
        return IotDevice::factory()->create([
            'hive_id' => $hive->id,
            'hardware_team_id' => IotHardwareTeam::factory(),
            'status' => 'deployed',
        ]);
    }

    /** @param  array<string, float|null>  $zones */
    private function recordTemperature(array $zones, $recordedAt, ?Hive $hive = null, bool $suspect = false, $receivedAt = null): HiveTemperature
    {
        $hive ??= $this->hive;

        return HiveTemperature::create([
            'hive_id' => $hive->id,
            'device_id' => $hive->is($this->hive) ? $this->device->id : $this->deviceFor($hive)->id,
            'honey_section' => $zones['honey_section'] ?? null,
            'brood_section' => $zones['brood_section'] ?? null,
            'exterior' => $zones['exterior'] ?? null,
            'suspect' => $suspect,
            'recorded_at' => $recordedAt,
            'created_at' => $receivedAt ?? now(),
        ]);
    }
}
