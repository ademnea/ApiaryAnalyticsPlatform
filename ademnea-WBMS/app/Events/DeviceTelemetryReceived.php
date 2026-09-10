<?php

namespace App\Events;

use App\Models\IotDevice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceTelemetryReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public IotDevice $device)
    {
    }
}
