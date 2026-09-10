<?php

namespace App\Events;

use App\Models\Hive;
use App\Models\IotDevice;
use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SensorRecordReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public IotDevice $device,
        public Hive $hive,
        public string $sensorType,
        public Carbon $recordedAt,
    ) {
    }
}
