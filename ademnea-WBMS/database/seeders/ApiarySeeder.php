<?php

namespace Database\Seeders;

use App\Models\Apiary;
use Illuminate\Database\Seeder;

class ApiarySeeder extends Seeder
{
    public function run(): void
    {
        Apiary::firstOrCreate(
            ['name' => 'Mukono Central Apiary', 'country' => 'Uganda', 'managing_entity' => 'Makerere University'],
            [
                'region' => 'Central Region',
                'hive_capacity' => 20,
                'contact_name' => 'Nakato Prossy',
                'contact_phone' => '+256701234567',
                'contact_email' => 'nakato.prossy@ademnea.org',
                'status' => 'active',
                'is_active' => true,
            ]
        );

        Apiary::firstOrCreate(
            ['name' => 'Jinja Riverside Apiary', 'country' => 'Uganda', 'managing_entity' => 'Makerere University'],
            [
                'region' => 'Eastern Region',
                'hive_capacity' => 15,
                'contact_name' => 'Okello Simon',
                'contact_phone' => '+256709876543',
                'contact_email' => 'okello.simon@ademnea.org',
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }
}