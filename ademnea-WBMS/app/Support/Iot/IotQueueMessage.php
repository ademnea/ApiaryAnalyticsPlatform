<?php

namespace App\Support\Iot;

final class IotQueueMessage
{
    public function __construct(
        public readonly array $envelope,
        public readonly ?string $receiptHandle = null,
        public readonly int $deliveryAttempt = 1,
    ) {}
}