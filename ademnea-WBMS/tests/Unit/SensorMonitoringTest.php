<?php

namespace Tests\Unit;

use App\Enums\MediaKind;
use App\Enums\SensorMetric;
use App\Services\Monitoring\MonitoringFilters;
use App\Services\Monitoring\MonitoringService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Database-free tests for the Sensor Monitoring filters, chart buckets and streams. */
class SensorMonitoringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-11 12:00:00'));
    }

    // ---- Time window --------------------------------------------------------

    #[Test]
    public function it_defaults_to_the_last_seven_days_with_no_scope(): void
    {
        $filters = MonitoringFilters::fromArray([]);

        $this->assertSame('7d', $filters->range);
        $this->assertTrue($filters->from->equalTo(CarbonImmutable::now()->subDays(7)));
        $this->assertTrue($filters->to->equalTo(CarbonImmutable::now()));
        $this->assertFalse($filters->hasAnyScope());
    }

    #[Test]
    #[DataProvider('presets')]
    public function each_preset_is_a_window_ending_now(string $range, int $days): void
    {
        $filters = MonitoringFilters::fromArray(['range' => $range]);

        $this->assertSame($range, $filters->range);
        $this->assertSame($days, (int) round($filters->from->diffInDays($filters->to)));
        $this->assertTrue($filters->to->equalTo(CarbonImmutable::now()));
    }

    public static function presets(): array
    {
        return ['24 hours' => ['24h', 1], '7 days' => ['7d', 7], '30 days' => ['30d', 30], '90 days' => ['90d', 90]];
    }

    #[Test]
    public function an_unknown_range_or_an_empty_custom_range_falls_back_to_the_default(): void
    {
        $this->assertSame('7d', MonitoringFilters::fromArray(['range' => '5y'])->range);
        $this->assertSame('7d', MonitoringFilters::fromArray(['range' => 'custom'])->range);
    }

    #[Test]
    public function typed_dates_make_a_whole_day_custom_window_even_with_a_preset(): void
    {
        $filters = MonitoringFilters::fromArray(['range' => '30d', 'from' => '2026-09-01', 'to' => '2026-09-05']);

        $this->assertTrue($filters->isCustom());
        $this->assertSame('2026-09-01 00:00:00', $filters->from->toDateTimeString());
        $this->assertSame('2026-09-05 23:59:59', $filters->to->toDateTimeString());
        $this->assertSame('Sep 1, 2026 — Sep 5, 2026', $filters->windowLabel());
        $this->assertSame(['range' => 'custom', 'from' => '2026-09-01', 'to' => '2026-09-05'], $filters->queryString());
    }

    #[Test]
    public function an_inverted_window_is_reordered_and_an_overlong_one_is_capped(): void
    {
        $inverted = MonitoringFilters::fromArray(['from' => '2026-09-05', 'to' => '2026-09-01']);
        $this->assertTrue($inverted->from->lessThan($inverted->to));

        $overlong = MonitoringFilters::fromArray(['from' => '2024-01-01', 'to' => '2026-09-10']);
        $this->assertEqualsWithDelta(MonitoringFilters::MAX_RANGE_DAYS, $overlong->from->diffInDays($overlong->to), 0.001);
    }

    #[Test]
    public function a_chosen_hive_replaces_the_apiary(): void
    {
        $filters = MonitoringFilters::fromArray(['apiary_id' => '3', 'hive_id' => '5', 'flagged' => '1']);

        $this->assertNull($filters->apiaryId);
        $this->assertSame(5, $filters->hiveId);
        $this->assertSame(['hive_id' => 5, 'range' => '7d', 'flagged' => 1], $filters->queryString());
    }

    #[Test]
    public function the_flagged_filter_accepts_form_style_booleans(): void
    {
        $this->assertFalse(MonitoringFilters::fromArray(['flagged' => '0'])->flaggedOnly);
        $this->assertTrue(MonitoringFilters::fromArray(['flagged' => 'on'])->flaggedOnly);
    }

    // ---- Table preferences --------------------------------------------------

    #[Test]
    public function table_preferences_travel_in_the_query_string_but_the_snapshot_does_not(): void
    {
        $filters = MonitoringFilters::fromArray(['per_page' => '50', 'sort' => 'received', 'upto' => '812']);

        $this->assertSame(50, $filters->perPage);
        $this->assertSame('created_at', $filters->sortColumn());
        $this->assertSame(812, $filters->upto);
        $this->assertSame(['range' => '7d', 'per_page' => 50, 'sort' => 'received'], $filters->queryString());
        $this->assertFalse($filters->hasAnyScope(), 'Sorting or paging does not narrow the data.');
    }

    #[Test]
    public function unknown_table_preferences_fall_back_to_the_defaults(): void
    {
        $filters = MonitoringFilters::fromArray(['per_page' => '7', 'sort' => 'hive']);

        $this->assertSame(MonitoringFilters::DEFAULT_PER_PAGE, $filters->perPage);
        $this->assertSame('recorded_at', $filters->sortColumn());
        $this->assertNull($filters->upto);
    }

    // ---- Chart buckets ------------------------------------------------------

    #[Test]
    public function windows_up_to_a_week_are_hourly_and_longer_ones_daily(): void
    {
        $this->assertTrue(MonitoringFilters::fromArray(['range' => '24h'])->isHourly());
        $this->assertTrue(MonitoringFilters::fromArray(['range' => '7d'])->isHourly());
        $this->assertFalse(MonitoringFilters::fromArray(['range' => '30d'])->isHourly());
    }

    #[Test]
    public function hourly_buckets_cover_every_hour_including_empty_ones(): void
    {
        $filters = MonitoringFilters::fromArray(['from' => '2026-09-10', 'to' => '2026-09-10']);
        $keys = $filters->bucketKeys();

        $this->assertCount(24, $keys);
        $this->assertSame('2026-09-10 00:00:00', $keys[0]);
        $this->assertSame('2026-09-10 23:00:00', $keys[23]);
        $this->assertSame("DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00:00')", $filters->bucketSql());
        $this->assertSame('Sep 10, 14:00', $filters->bucketLabel('2026-09-10 14:00:00'));
    }

    #[Test]
    public function daily_buckets_match_the_sql_date_shape(): void
    {
        $filters = MonitoringFilters::fromArray(['from' => '2026-08-01', 'to' => '2026-08-20']);
        $keys = $filters->bucketKeys();

        $this->assertCount(20, $keys);
        $this->assertSame(['2026-08-01', '2026-08-20'], [$keys[0], $keys[19]]);
        $this->assertSame("DATE_FORMAT(recorded_at, '%Y-%m-%d')", $filters->bucketSql());
        $this->assertSame('Aug 1', $filters->bucketLabel('2026-08-01'));
    }

    #[Test]
    public function day_keys_are_daily_even_when_the_chart_is_hourly(): void
    {
        $filters = MonitoringFilters::fromArray(['from' => '2026-09-08', 'to' => '2026-09-10']);

        $this->assertTrue($filters->isHourly());
        $this->assertSame(['2026-09-08', '2026-09-09', '2026-09-10'], $filters->dayKeys());
    }

    #[Test]
    public function no_preset_charts_more_than_169_points(): void
    {
        foreach (array_keys(MonitoringFilters::PRESETS) as $range) {
            $this->assertLessThanOrEqual(169, count(MonitoringFilters::fromArray(['range' => $range])->bucketKeys()), $range);
        }
    }

    // ---- Streams and freshness ----------------------------------------------

    #[Test]
    public function sensor_values_match_the_ingestion_vocabulary_and_zones(): void
    {
        $this->assertSame(['temperature', 'humidity', 'co2', 'weight'], array_column(SensorMetric::cases(), 'value'));

        $this->assertSame(['honey_section', 'brood_section', 'exterior'], array_keys(SensorMetric::Humidity->columns()));
        $this->assertSame('brood_section', SensorMetric::Temperature->primaryColumn());
        $this->assertSame('co2_level', SensorMetric::Co2->primaryColumn());
        $this->assertSame('weight_kg', SensorMetric::Weight->primaryColumn());
        $this->assertFalse(SensorMetric::Weight->isMultiZone());
    }

    #[Test]
    public function every_stream_keeps_its_named_route(): void
    {
        $this->assertTrue(Route::has('admin.monitoring.index'));
        $this->assertTrue(Route::has('admin.monitoring.insights'));
        $this->assertTrue(Route::has('admin.monitoring.export'));

        foreach ([...SensorMetric::cases(), ...MediaKind::cases()] as $stream) {
            $this->assertTrue(Route::has($stream->routeName()), $stream->routeName());
        }
    }

    #[Test]
    public function a_stream_is_stale_after_the_offline_threshold(): void
    {
        $this->assertTrue(MonitoringService::isStale(null, 120));
        $this->assertFalse(MonitoringService::isStale(CarbonImmutable::now()->subMinutes(119), 120));
        $this->assertTrue(MonitoringService::isStale(CarbonImmutable::now()->subMinutes(121), 120));
    }

    #[Test]
    public function an_old_reading_that_just_arrived_is_a_backlog_not_an_outage(): void
    {
        $now = CarbonImmutable::now();

        $this->assertTrue(MonitoringService::isBacklog($now->subDays(3), $now->subMinute(), 120));
        $this->assertFalse(MonitoringService::isBacklog($now->subDays(3), $now->subDays(3), 120), 'Silent device: stale.');
        $this->assertFalse(MonitoringService::isBacklog($now->subMinutes(5), $now->subMinute(), 120), 'Current data: live.');
        $this->assertFalse(MonitoringService::isBacklog(null, $now, 120));
    }

    #[Test]
    public function only_a_meaningful_delivery_delay_is_reported(): void
    {
        $recorded = CarbonImmutable::parse('2026-09-11 08:00:00');

        $this->assertNull(MonitoringService::deliveryDelay($recorded, $recorded->addMinutes(MonitoringService::LATE_AFTER_MINUTES)));
        $this->assertNull(MonitoringService::deliveryDelay($recorded, $recorded->subMinutes(30)), 'A device clock ahead of the server is not "late".');
        $this->assertNull(MonitoringService::deliveryDelay($recorded, null));
        $this->assertSame('3 days', MonitoringService::deliveryDelay($recorded, $recorded->addDays(3)->addHours(4)));
    }
}
