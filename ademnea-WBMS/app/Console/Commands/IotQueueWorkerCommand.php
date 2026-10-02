<?php

namespace App\Console\Commands;

use App\Contracts\IotQueueTransportContract;
use App\Services\Iot\IotIngestEnvelopeProcessor;
use Illuminate\Console\Command;
use Throwable;

class IotQueueWorkerCommand extends Command
{
    protected $signature = 'iot:work {--wait=10 : Seconds to block waiting for a message before polling again}';
    protected $description = 'Continuously consumes raw IoT envelopes from the queue transport (Redis mock or SQS) and processes them.';

    private const RESOURCE_MAP = [
        '/ingest' => 'sensor_reading',
        '/heartbeat' => 'heartbeat',
        '/media/confirm' => 'media_confirmation',
    ];

    private bool $shouldStop = false;

    public function handle(IotQueueTransportContract $transport, IotIngestEnvelopeProcessor $processor): int
    {
        // Finish the current message before exiting when systemd/supervisor/deploys stop us.
        $this->trap([SIGTERM, SIGINT], function () {
            $this->shouldStop = true;
        });

        $this->info('Worker started. Driver: '.config('services.iot.queue_driver'));

        while (! $this->shouldStop) {
            $message = $transport->pop((int) $this->option('wait'));

            if (! $message) {
                continue;
            }

            $resource = $message->envelope['resource'] ?? null;
            $messageType = self::RESOURCE_MAP[$resource] ?? null;
            $requestId = $message->envelope['requestId'] ?? '-';

            if (! $messageType) {
                $this->error("[{$requestId}] Unrecognized resource: ".($resource ?? 'MISSING'));
                $transport->fail($message, "Unrecognized resource path: {$resource}");
                continue;
            }

            $apiKey = trim($message->envelope['headers']['X-Api-Key'] ?? '');
            $payload = $message->envelope['body'] ?? [];

            if (! is_array($payload)) {
                $this->error("[{$requestId}] Body is not a JSON object.");
                $transport->fail($message, 'Body is not a JSON object.');
                continue;
            }

            // Per-message detail only with -v, so production logs stay readable.
            $this->line(
                "[{$requestId}] {$messageType}; key ".($apiKey ? 'present' : 'MISSING').'; fields: '.implode(', ', array_keys($payload)),
                null,
                'v'
            );

            try {
                $processor->process($messageType, $payload, $apiKey);
                $transport->acknowledge($message);
                $this->line("[{$requestId}] processed", null, 'v');
            } catch (Throwable $e) {
                $this->error("[{$requestId}] {$e->getMessage()}");
                report($e);
                $transport->fail($message, $e->getMessage());
            }
        }

        $this->info('Worker stopped.');

        return self::SUCCESS;
    }
}
