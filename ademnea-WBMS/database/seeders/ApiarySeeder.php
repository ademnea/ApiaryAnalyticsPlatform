<?php

namespace Database\Seeders;

use App\Models\Apiary;
use App\Services\ApiaryManagement\ApiaryCodeGenerator;
use Illuminate\Database\Seeder;

class ApiarySeeder extends Seeder
{
    public function run(): void
    {
        Apiary::updateOrCreate(
            ['name' => 'Mukono Central Apiary', 'country' => 'UG'],
            [
                'managing_entity' => 'Makerere University',
                'region' => 'Central Region',
                'status' => 'Active',
                'apiary_code' => ApiaryCodeGenerator::generate('Mukono Central Apiary', 'UG'),
            ]
        );

        Apiary::updateOrCreate(
            ['name' => 'Jinja Riverside Apiary', 'country' => 'UG'],
            [
                'managing_entity' => 'Makerere University',
                'region' => 'Eastern Region',
                'status' => 'Active',
                'apiary_code' => ApiaryCodeGenerator::generate('Jinja Riverside Apiary', 'UG'),
        Apiary::firstOrCreate(
            [
                'name'    => 'Mukono Central Apiary',
                'country' => 'UG',
            ],
            [
                'apiary_code'   => 'MUK-C',      // unique identifier
                'region'        => 'Central Region',
                'district'      => 'Mukono',
                'hive_capacity' => 20,
                'description'   => 'Contact: Nakato Prossy, +256701234567',
                'status'        => 'Active',
                'farmer_id'     => null,                // adjust if you have a farmer to link
            ]
        );

        Apiary::firstOrCreate(
            [
                'name'    => 'Jinja Riverside Apiary',
                'country' => 'UG',
            ],
            [
                'apiary_code'   => 'JIN-R',
                'region'        => 'Eastern Region',
                'district'      => 'Jinja',
                'hive_capacity' => 15,
                'description'   => 'Contact: Okello Simon, +256709876543',
                'status'        => 'Active',
                'farmer_id'     => null,
            ]
        );
    }
}