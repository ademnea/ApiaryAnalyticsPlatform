<?php

namespace Database\Factories;

use App\Models\HarvestRecord;
use App\Models\Hive;
use Illuminate\Database\Eloquent\Factories\Factory;

class HarvestRecordFactory extends Factory
{
    protected $model = HarvestRecord::class;

    public function definition(): array
    {
        return [
            'hive_id' => Hive::factory(),
            'harvest_date' => $this->faker->date(),
            'honey_yield_kg' => $this->faker->optional()->randomFloat(2, 0, 100),
            'beeswax_yield_kg' => $this->faker->optional()->randomFloat(2, 0, 20),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
