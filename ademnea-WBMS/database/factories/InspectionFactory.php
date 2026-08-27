<?php

namespace Database\Factories;

use App\Models\Hive;
use App\Models\Inspection;
use Illuminate\Database\Eloquent\Factories\Factory;

class InspectionFactory extends Factory
{
    protected $model = Inspection::class;

    public function definition(): array
    {
        return [
            'hive_id' => Hive::factory(),
            'inspected_at' => $this->faker->date(),
            'strength_rating' => $this->faker->optional()->randomElement(['Strong', 'Moderate', 'Weak']),
            'disease_events' => $this->faker->optional()->sentence(),
            'queen_status_notes' => $this->faker->optional()->sentence(),
            'general_notes' => $this->faker->optional()->sentence(),
        ];
    }
}
