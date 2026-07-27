<?php

namespace Database\Seeders;

use App\Models\Apiary;
use App\Models\Hive;
use Illuminate\Database\Seeder;

class HiveSeeder extends Seeder
{
    public function run(): void
    {
        $mukono = Apiary::where('name', 'Mukono Central Apiary')->firstOrFail();
        $jinja = Apiary::where('name', 'Jinja Riverside Apiary')->firstOrFail();

        // 6 hives at Mukono
        for ($i = 1; $i <= 6; $i++) {
            $code = sprintf('HIVE-UG-MUK-%03d', $i);

            Hive::firstOrCreate(
                ['hive_code' => $code],
                [
                    'apiary_id' => $mukono->id,
                    'display_name' => "Mukono Colony {$i}",
                    'hive_type' => 'Langstroth',
                    'construction_material' => 'Pine wood',
                    'installation_date' => now()->subMonths(rand(1, 18)),
                    'colony_origin' => 'purchased package',
                    'queen_status' => 'present',
                    'status' => 'active',
                    'gps_latitude' => 0.3533 + (rand(-50, 50) / 10000),
                    'gps_longitude' => 32.7553 + (rand(-50, 50) / 10000),
                    'gps_accuracy_meters' => rand(3, 12),
                    'last_inspection_date' => now()->subDays(rand(1, 45)),
                ]
            );
        }

        // 4 hives at Jinja
        for ($i = 1; $i <= 4; $i++) {
            $code = sprintf('HIVE-UG-JIN-%03d', $i);

            Hive::firstOrCreate(
                ['hive_code' => $code],
                [
                    'apiary_id' => $jinja->id,
                    'display_name' => "Jinja Colony {$i}",
                    'hive_type' => 'Top-Bar',
                    'construction_material' => 'Cedar wood',
                    'installation_date' => now()->subMonths(rand(1, 12)),
                    'colony_origin' => 'wild capture',
                    'queen_status' => 'present',
                    'status' => 'active',
                    'gps_latitude' => 0.4478 + (rand(-50, 50) / 10000),
                    'gps_longitude' => 33.2026 + (rand(-50, 50) / 10000),
                    'gps_accuracy_meters' => rand(3, 12),
                    'last_inspection_date' => now()->subDays(rand(1, 45)),
                ]
            );
        }
    }
}