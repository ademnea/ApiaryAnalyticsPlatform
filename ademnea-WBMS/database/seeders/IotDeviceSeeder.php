<?php

namespace Database\Seeders;

use App\Models\Hive;
use App\Models\IotDevice;
use App\Models\IotHardwareTeam;
use App\Services\IotDeviceRegistryService;
use Illuminate\Database\Seeder;

class IotDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $teams = IotHardwareTeam::all();
        $hives = Hive::all();

        if ($teams->isEmpty() || $hives->isEmpty()) {
            $this->command->error('Run IotHardwareTeamSeeder and HiveSeeder before IotDeviceSeeder.');
            return;
        }

        $registry = app(IotDeviceRegistryService::class);

        // 8 devices assigned to a hive (one per seeded hive, first 8),
        // 2 left unassigned on purpose — to exercise the "device not
        // assigned to a hive" rejection path during testing.
        $plan = [
            ['code' => 'AEU-UG-001', 'type' => 'numeric_sensor', 'hive' => 0],
            ['code' => 'AEU-UG-002', 'type' => 'numeric_sensor', 'hive' => 1],
            ['code' => 'AEU-UG-003', 'type' => 'numeric_sensor', 'hive' => 2],
            ['code' => 'AEU-UG-004', 'type' => 'numeric_sensor', 'hive' => 3],
            ['code' => 'AEU-UG-005', 'type' => 'media_capture', 'hive' => 4],
            ['code' => 'AEU-UG-006', 'type' => 'media_capture', 'hive' => 5],
            ['code' => 'AEU-UG-007', 'type' => 'combo', 'hive' => 6],
            ['code' => 'AEU-UG-008', 'type' => 'combo', 'hive' => 7],
            ['code' => 'AEU-UG-009', 'type' => 'numeric_sensor', 'hive' => null], // unassigned
            ['code' => 'AEU-UG-010', 'type' => 'media_capture', 'hive' => null], // unassigned
        ];

        $rows = [];

        foreach ($plan as $i => $item) {
            if (IotDevice::where('device_code', $item['code'])->exists()) {
                continue; // idempotent re-run
            }

            $team = $teams[$i % $teams->count()];
            $hiveId = $item['hive'] !== null ? $hives[$item['hive']]->id : null;

            $result = $registry->register([
                'device_code' => $item['code'],
                'device_type' => $item['type'],
                'hardware_team_id' => $team->id,
                'hive_id' => $hiveId,
            ]);

            // register() leaves new devices in 'provisioned' status even
            // when a hive_id is passed directly here (assignToHive() is
            // the only path that auto-bumps status) — bump it manually so
            // seeded, hive-assigned devices reflect reality for testing.
            if ($hiveId !== null) {
                $result['device']->update(['status' => 'deployed']);
            }

            $rows[] = [
                'device_code' => $item['code'],
                'type' => $item['type'],
                'hive' => $hiveId ? $hives[$item['hive']]->hive_code : '— unassigned —',
                'plaintext_api_key' => $result['plaintext_key'],
            ];
        }

        if (! empty($rows)) {
            $this->command->info('Seeded IoT devices -copy the API keys:');
            $this->command->table(['Device Code', 'Type', 'Hive', 'Plaintext API Key'], $rows);
        }
    }
}