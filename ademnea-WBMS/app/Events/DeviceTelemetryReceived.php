<?php

namespace App\Events;

use App\Models\IotDevice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DeviceTelemetryReceived
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array  $reading  the telemetry values carried by this heartbeat
     * @param  Carbon|null  $recordedAt  when the heartbeat was sent
     * @param  bool  $isLatest  false for an out-of-order heartbeat older than the device's current state
     */
    public function __construct(
        public IotDevice $device,
        public array $reading = [],
        public ?Carbon $recordedAt = null,
        public bool $isLatest = true,
    ) {
    }
}
