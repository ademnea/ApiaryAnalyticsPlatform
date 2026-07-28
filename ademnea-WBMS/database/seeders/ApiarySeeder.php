<?php

namespace Database\Seeders;

use App\Models\Apiary;
use Illuminate\Database\Seeder;

class ApiarySeeder extends Seeder
{
    public function run(): void
    {
        Apiary::firstOrCreate(
            ['name' => 'Mukono Central Apiary', 'country' => 'UG'],
            [
                'managing_entity' => 'Makerere University',
                'region' => 'Central Region',
                'status' => 'Active',
            ]
        );

        Apiary::firstOrCreate(
            ['name' => 'Jinja Riverside Apiary', 'country' => 'UG'],
            [
                'managing_entity' => 'Makerere University',
                'region' => 'Eastern Region',
                'status' => 'Active',
            ]
        );
    }
}