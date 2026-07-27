<?php

namespace App\Contracts;

use App\Support\Iot\IotQueueMessage;

interface IotQueueTransportContract
{
    public function pop(int $waitSeconds): ?IotQueueMessage;

    /** Called after successful processing — removes the message for good. */
    public function acknowledge(IotQueueMessage $message): void;

    /** Called after a processing failure — requeue/dead-letter per transport semantics. */
    public function fail(IotQueueMessage $message, string $reason): void;
}