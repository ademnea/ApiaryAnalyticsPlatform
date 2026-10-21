<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledReport;
use App\Models\ScheduledReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchDueReports extends Command
{
    protected $signature = 'reports:dispatch-due';

    protected $description = 'Queue scheduled sensor and anomaly reports that are due';

    public function handle(): int
    {
        DB::transaction(function (): void {
            $due = ScheduledReport::query()
                ->where('is_active', true)
                ->whereNotNull('next_run_at')
                ->where('next_run_at', '<=', now())
                ->orderBy('next_run_at')
                ->lockForUpdate()
                ->get();

            foreach ($due as $report) {
                $runAt = $report->next_run_at->setTimezone($report->timezone);
                $report->forceFill([
                    'next_run_at' => $report->frequency === 'once' ? null : $report->nextRunAfter($runAt),
                    'is_active' => $report->frequency === 'once' ? false : $report->is_active,
                ])->save();

                SendScheduledReport::dispatch($report->id, $runAt->toIso8601String())->afterCommit();
            }
        });

        return self::SUCCESS;
    }
}
