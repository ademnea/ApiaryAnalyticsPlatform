<?php

namespace Database\Factories;

use App\Models\Farmer;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmerFactory extends Factory
{
    protected $model = Farmer::class;

    public function definition(): array
    {
        return [
            'first_name'    => $this->faker->firstName(),
            'last_name'     => $this->faker->lastName(),
            'email'         => $this->faker->unique()->safeEmail(),
            'phone'         => $this->faker->phoneNumber(),
            'phone_secondary' => $this->faker->optional()->phoneNumber(),
            'country'       => $this->faker->randomElement(['UG', 'SS', 'TZ']),
            'region'        => $this->faker->optional()->state(),
            'village'       => $this->faker->optional()->city(),
            'national_id'   => $this->faker->optional()->unique()->numerify('##########'),
            'status'        => 'Active',
            'profile_status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Active',
            'profile_status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Inactive',
            'profile_status' => 'inactive',
        ]);
    }
}
