<?php

namespace App\Services;

use App\Models\IotDevice;
use App\Exceptions\IotDeviceNotAssignedException;

class IotDeviceIdentificationService
{
    /**
     * Resolves the hive (and its apiary) a device's incoming data belongs
     * to, from the device's current hive_id assignment.
     *
     * This is a fast, current-state lookup only — it deliberately does NOT
     * consult Apiary Management's historical hive_device_assignments table
     * (see §4.5.9 decision #3: iot_devices.hive_id is a pointer kept in
     * sync with that table, not a replacement for it).
     *
     * @return array{hive: \App\Models\Hive, apiary: \App\Models\Apiary}
     *
     * @throws IotDeviceNotAssignedException if the device has no active
     *         hive assignment — payload cannot be stored without a
     *         resolvable hive, per the data-integrity rule that every
     *         sensor record must belong to a registered hive.
     */
    public function resolveHiveAndApiary(IotDevice $device): array
    {
        $hive = $device->hive()->first();

        if (! $hive) {
            throw new IotDeviceNotAssignedException(
                "Device {$device->device_code} has no active hive assignment; payload cannot be stored."
            );
        }

        // Owned by the Apiary Management module (Developer B) —
        // Hive::apiary() is the current, single physical-site relationship
        // (the earlier Farm → Hive hierarchy has been retired).
        $apiary = $hive->apiary;

        return ['hive' => $hive, 'apiary' => $apiary];
    }
}