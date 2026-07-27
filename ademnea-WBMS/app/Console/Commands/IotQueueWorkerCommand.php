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

   public function handle(IotQueueTransportContract $transport, IotIngestEnvelopeProcessor $processor): int
{
    $this->info('Worker started. Driver: ' . config('services.iot.queue_driver'));

    while (true) {
        $this->line('Polling...');
        $message = $transport->pop((int) $this->option('wait'));

        if (! $message) {
            $this->line('No message.');
            continue;
        }

        $this->info('Got a message!');
        $resource = $message->envelope['resource'] ?? null;
        $this->line('Resource: ' . ($resource ?? 'MISSING'));

        $messageType = self::RESOURCE_MAP[$resource] ?? null;
        if (! $messageType) {
            $this->error("Unrecognized resource: {$resource}");
            $transport->fail($message, "Unrecognized resource path: {$resource}");
            continue;
        }

        $this->line('Message type: ' . $messageType);

        $apiKey = trim($message->envelope['headers']['X-Api-Key'] ?? '');
        $payload = $message->envelope['body'] ?? [];
        $this->line('API key: ' . ($apiKey ? 'present' : 'MISSING'));
        $this->line('Payload keys: ' . implode(', ', array_keys($payload)));

        try {
            $this->line('Calling processor...');
            $processor->process($messageType, $payload, $apiKey);
            $this->info('Processor finished.');
            $transport->acknowledge($message);
        } catch (Throwable $e) {
            $this->error('Exception: ' . $e->getMessage());
            report($e);
            $transport->fail($message, $e->getMessage());
        }
    }
}
}