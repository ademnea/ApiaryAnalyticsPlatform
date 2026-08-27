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
            ]
        );
    }
}