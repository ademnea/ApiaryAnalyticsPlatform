<?php

namespace Database\Seeders;

use App\Models\IotHardwareTeam;
use Illuminate\Database\Seeder;

class IotHardwareTeamSeeder extends Seeder
{
    public function run(): void
    {
        IotHardwareTeam::firstOrCreate(
            ['name' => 'Makerere Field Deployment Team'],
            [
                'country' => 'Uganda',
                'contact_email' => 'fieldteam.mak@ademnea.org',
                'contact_phone' => '+256700111222',
                'is_active' => true,
            ]
        );

        IotHardwareTeam::firstOrCreate(
            ['name' => 'NTNU Hardware Support Unit'],
            [
                'country' => 'Uganda',
                'contact_email' => 'hardware.ntnu@ademnea.org',
                'contact_phone' => '+256700333444',
                'is_active' => true,
            ]
        );
    }
}