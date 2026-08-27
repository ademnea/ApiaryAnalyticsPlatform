<?php

namespace App\Services\Iot\Transport;

use App\Contracts\IotQueueTransportContract;
use App\Support\Iot\IotQueueMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisIotQueueTransport implements IotQueueTransportContract
{
    public function __construct(
        private readonly string $queueKey,
        private readonly string $deadLetterKey,
        private readonly int $maxAttempts,
        private readonly string $connection = 'default',
    ) {}

    public function pop(int $waitSeconds): ?IotQueueMessage
    {
        // BRPOP is destructive — the message is already off the queue the
        // moment this returns. Redis has no separate "delete" step the way
        // SQS does, which is why acknowledge() below is a no-op.
        $result = Redis::connection($this->connection)->brpop([$this->queueKey], $waitSeconds);

        if (! $result) {
            return null;
        }

        $envelope = json_decode($result[1], true);
        $attempts = (int) ($envelope['_delivery_attempt'] ?? 1);

        return new IotQueueMessage($envelope, null, $attempts);
    }

    public function acknowledge(IotQueueMessage $message): void
    {
        // Nothing to do — BRPOP already removed it permanently.
    }

    public function fail(IotQueueMessage $message, string $reason): void
    {
        $nextAttempt = $message->deliveryAttempt + 1;
        $envelope = $message->envelope;

        if ($nextAttempt > $this->maxAttempts) {
            $envelope['_failure_reason'] = $reason;
            $envelope['_failed_at'] = now()->toIso8601String();

            Redis::connection($this->connection)->lpush($this->deadLetterKey, json_encode($envelope));

            Log::critical('IoT message moved to dead-letter list after max attempts.', [
                'reason' => $reason,
                'requestId' => $envelope['requestId'] ?? null,
            ]);

            return;
        }

        $envelope['_delivery_attempt'] = $nextAttempt;
        Redis::connection($this->connection)->lpush($this->queueKey, json_encode($envelope));
    }
}