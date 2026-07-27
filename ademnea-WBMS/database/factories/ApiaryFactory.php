<?php

namespace Database\Factories;

use App\Models\Apiary;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiaryFactory extends Factory
{
    protected $model = Apiary::class;

    public function definition(): array
    {
        return [
            'name'             => $this->faker->unique()->company() . ' Apiary',
            'country'          => $this->faker->country(),
            'region'           => $this->faker->optional()->state(),
            'managing_entity'  => $this->faker->optional()->company(),
            'hive_capacity'    => $this->faker->numberBetween(0, 200),
            'contact_name'     => $this->faker->optional()->name(),
            'contact_phone'    => $this->faker->optional()->phoneNumber(),
            'contact_email'    => $this->faker->optional()->safeEmail(),
            'status'           => $this->faker->randomElement(['active', 'inactive', 'decommissioned']),
            'is_active'        => $this->faker->boolean(80), // 80% chance true
        ];
    }

    /**
     * Indicate that the apiary is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the apiary is inactive / decommissioned.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => 'inactive',
            'is_active' => false,
        ]);
    }
}