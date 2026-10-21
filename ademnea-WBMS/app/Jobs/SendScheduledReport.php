<?php

namespace App\Jobs;

use App\Mail\ScheduledMonitoringReportMail;
use App\Models\NotificationLog;
use App\Models\ScheduledReport;
use App\Services\Reports\ScheduledReportData;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendScheduledReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public int $scheduledReportId, public string $scheduledFor) {}

    public function handle(ScheduledReportData $reports): void
    {
        $report = ScheduledReport::find($this->scheduledReportId);

        if (! $report) {
            return;
        }

        $runAt = CarbonImmutable::parse($this->scheduledFor);
        $data = $reports->build($report, $runAt);
        $csv = $reports->csv($data);

        foreach ($reports->recipients($report) as $recipient) {
            $log = NotificationLog::firstOrNew([
                'scheduled_report_id' => $report->id,
                'scheduled_report_run_at' => $runAt,
                'recipient' => $recipient,
            ]);

            if ($log->status === 'sent') {
                continue;
            }

            $log->fill([
                'recipient_type' => 'report_recipient',
                'type' => 'scheduled_report',
                'channel' => 'email',
                'subject' => "AdEMNEA report: {$report->name}",
                'content' => "Sensor and anomaly report for {$data['scope']} from {$data['period_from']->toDateString()} to {$data['period_to']->toDateString()}.",
                'status' => 'pending',
                'attempts' => (int) $log->attempts + 1,
                'error_message' => null,
            ])->save();

            try {
                Mail::to($recipient)->send(new ScheduledMonitoringReportMail($report->name, $data, true, $csv));
                $log->update(['status' => 'sent', 'sent_at' => now()]);
            } catch (Throwable $exception) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 1000),
                ]);
                $report->update(['last_error' => Str::limit($exception->getMessage(), 1000)]);

                throw $exception;
            }
        }

        $report->update(['last_sent_at' => now(), 'last_error' => null]);
    }

    public function failed(Throwable $exception): void
    {
        ScheduledReport::whereKey($this->scheduledReportId)->update([
            'last_error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
