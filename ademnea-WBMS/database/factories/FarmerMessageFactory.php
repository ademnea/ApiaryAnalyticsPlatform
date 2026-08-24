<?php

namespace Database\Factories;

use App\Models\Farmer;
use App\Models\FarmerMessage;
use App\Models\Hive;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmerMessageFactory extends Factory
{
    protected $model = FarmerMessage::class;

    public function definition(): array
    {
        return [
            'farmer_id' => Farmer::factory(),
            'hive_id' => Hive::factory(),
            'subject' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'status' => 'sent',
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'sent']);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'read']);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'resolved']);
    }
}
