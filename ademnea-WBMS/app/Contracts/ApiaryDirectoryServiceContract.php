<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Owned by the Apiary Management module (Developer B). The IoT module only
 * depends on this interface — it never touches the apiaries/hives tables
 * directly. Implementation is currently mocked (see
 * App\Services\External\ApiaryDirectoryServiceMock) so the device
 * assignment interface can be built and demoed before Developer B's
 * module is ready.
 *
 * TO DEVELOPER B: implement this against apiaries / hives /
 * hive_device_assignments and rebind it in AppServiceProvider in place of
 * the mock. Please don't change these two method signatures without
 * coordinating — the assign wizard (admin/iot-devices/assign.blade.php)
 * depends on this exact shape.
 */
interface ApiaryDirectoryServiceContract
{
    /**
     * All active apiaries, each carrying its primary/managing farmer's name,
     * for step 1 of the device assignment wizard.
     * Expected object shape per item: id, name, farmer_name, country.
     */
    public function listApiariesWithPrimaryFarmer(): Collection;

    /**
     * Hives at the given apiary that do NOT already have an active device
     * of $deviceType assigned (a hive should not carry two devices of the
     * same sensor type). Used for step 2 of the wizard.
     * Expected object shape per item: id, hybrid_code, display_name.
     */
    public function listHivesAvailableForDeviceType(int $apiaryId, string $deviceType): Collection;
}