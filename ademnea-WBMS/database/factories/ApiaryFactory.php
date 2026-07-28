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
            'country'          => $this->faker->countryISOAlpha2(),
            'region'           => $this->faker->optional()->state(),
            'managing_entity'  => $this->faker->optional()->company(),
            'status'           => $this->faker->randomElement(['Active', 'Inactive', 'Under Maintenance']),
        ];
    }

    /**
     * Indicate that the apiary is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Active',
        ]);
    }

    /**
     * Indicate that the apiary is inactive / decommissioned.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Inactive',
        ]);
    }
}