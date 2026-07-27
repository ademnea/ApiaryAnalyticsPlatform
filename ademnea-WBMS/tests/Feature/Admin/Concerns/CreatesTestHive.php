<?php

namespace Tests\Feature\Admin\Concerns;

use Illuminate\Support\Facades\DB;

trait CreatesTestHive
{
    /**
     * Creates a minimal row in `hives` so FK-constrained assignment tests
     * have something real to point at. Apiary Management (Developer B)
     * owns this table's actual schema — prefer Hive::factory() once it
     * exists; this raw fallback only guarantees enough of a row to satisfy
     * fk_iot_devices_hive in the meantime.
     */
    protected function createTestHiveId(): int
    {
        if (class_exists(\App\Models\Hive::class)) {
            return \App\Models\Hive::factory()->create()->id;
        }

        return DB::table('hives')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}