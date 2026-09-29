<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaKind;
use App\Enums\SensorMetric;
use App\Http\Controllers\Controller;
use App\Services\Monitoring\MonitoringFilters;
use App\Services\Monitoring\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sensor Monitoring: the overview, the four sensor pages, hive insights, the
 * three media galleries and the CSV export.
 *
 * Each page also answers its own auto-refresh poll. When the browser sends
 * the refresh header, only the "monitoring-live" fragment is returned, built
 * from the same data as the full page.
 */
class MonitoringController extends Controller
{
    /** Seconds between background refreshes; matches the default device reporting interval. 0 turns it off. */
    public const AUTO_REFRESH_SECONDS = 300;

    private const REFRESH_HEADER = 'X-Monitoring-Refresh';

    public function __construct(private readonly MonitoringService $monitoring) {}

    public function index(Request $request): Response
    {
        $filters = MonitoringFilters::fromRequest($request);
        $hives = $this->monitoring->hives($filters);

        return $this->page($request, 'admin.monitoring.overview', $filters, null, [
            'sensorSnapshot' => $this->monitoring->sensorSnapshot($filters),
            'volume' => $this->monitoring->volumeSeries($filters),
            'mediaSnapshot' => $this->monitoring->mediaSnapshot($filters),
            'hives' => $hives,
            'coverage' => $this->monitoring->coverage($hives->pluck('id')->all(), $filters),
            'anomalyCount' => $this->monitoring->openAnomalies($filters),
        ]);
    }

    public function sensor(Request $request, SensorMetric $metric): Response
    {
        $filters = MonitoringFilters::fromRequest($request);
        $summary = $this->monitoring->summary($metric, $filters);

        return $this->page($request, 'admin.monitoring.sensor', $filters, $metric, [
            'metric' => $metric,
            'summary' => $summary,
            'chart' => $this->monitoring->series($metric, $filters),
            'weightChange' => $metric === SensorMetric::Weight ? $this->monitoring->weightDailyChange($filters) : null,
            'latestByDevice' => $this->monitoring->latestPerDevice($metric, $filters),
            'readings' => $this->monitoring->readings($metric, $filters, $summary['latest_id']),
            'newSinceSnapshot' => $this->monitoring->newSince($metric, $filters),
            'anomalyCount' => $this->monitoring->openAnomalies($filters, $metric),
        ]);
    }

    public function media(Request $request, MediaKind $kind): Response
    {
        $filters = MonitoringFilters::fromRequest($request);
        $summary = $this->monitoring->mediaSummary($kind, $filters);

        return $this->page($request, 'admin.monitoring.media', $filters, $kind, [
            'kind' => $kind,
            'summary' => $summary,
            'items' => $this->monitoring->mediaGallery($kind, $filters, $summary['latest_id']),
            'newSinceSnapshot' => $this->monitoring->newSince($kind, $filters),
        ]);
    }

    /** Paired charts for one hive. Nothing is queried until a hive is chosen. */
    public function insights(Request $request): Response
    {
        $filters = MonitoringFilters::fromRequest($request);

        return $this->page($request, 'admin.monitoring.insights', $filters, null, [
            'insights' => $filters->hiveId === null ? null : $this->monitoring->insights($filters),
        ], pageRoute: 'admin.monitoring.insights', pageLabel: 'Hive Insights');
    }

    public function export(Request $request, SensorMetric $metric): StreamedResponse
    {
        $filters = MonitoringFilters::fromRequest($request);
        $filename = "{$metric->value}-readings-{$filters->from->toDateString()}-to-{$filters->to->toDateString()}.csv";

        return response()->streamDownload(function () use ($metric, $filters): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF"); // byte-order mark, so Excel reads the file as UTF-8

            foreach ($this->monitoring->exportRows($metric, $filters) as $row) {
                fputcsv($out, $row, escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Renders a monitoring page, or only its live fragment for a refresh poll.
     * The fragment is never cached: it shares the page's URL, and the back
     * button must not show it in place of the page.
     *
     * @param  array<string, mixed>  $data
     * @param  ?string  $pageRoute  route of a page that is not a stream; defaults to the stream's or the overview's
     */
    private function page(Request $request, string $view, MonitoringFilters $filters, SensorMetric|MediaKind|null $stream, array $data, ?string $pageRoute = null, ?string $pageLabel = null): Response
    {
        $view = view($view, $data + [
            'stream' => $stream,
            'pageRoute' => $pageRoute ?? $stream?->routeName() ?? 'admin.monitoring.index',
            'pageLabel' => $pageLabel ?? $stream?->label(),
            'filters' => $filters,
            'apiaries' => $this->monitoring->apiaries(),
            'hiveGroups' => $this->monitoring->hiveGroups($filters->apiaryId),
            'staleAfterMinutes' => $this->monitoring->staleAfterMinutes(),
            'refreshSeconds' => self::AUTO_REFRESH_SECONDS,
        ]);

        if ($request->header(self::REFRESH_HEADER) !== '1') {
            return response($view)->header('Vary', self::REFRESH_HEADER);
        }

        return response($view->fragment('monitoring-live'))
            ->header('Vary', self::REFRESH_HEADER)
            ->header('Cache-Control', 'no-store, private');
    }
}
