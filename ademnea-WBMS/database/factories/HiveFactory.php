<?php

namespace Database\Factories;

use App\Models\Apiary;
use App\Models\Hive;
use Illuminate\Database\Eloquent\Factories\Factory;

class HiveFactory extends Factory
{
    protected $model = Hive::class;

    public function definition(): array
    {
        return [
            'apiary_id'              => Apiary::factory(),
            'hive_code'              => $this->faker->unique()->regexify('HIVE-[A-Z]{2}-[A-Z]{3}-[0-9]{3}'),
            'display_name'           => $this->faker->words(3, true),
            'hive_type'              => $this->faker->randomElement([
                'Langstroth', 'Top-Bar', 'Warre', 'Skep', 'Flow Hive', 'National'
            ]),
            'construction_material'  => $this->faker->optional()->randomElement([
                'Wood', 'Polystyrene', 'Plastic', 'Cement'
            ]),
            'installation_date'      => $this->faker->optional()->date(),
            'colony_origin'          => $this->faker->optional()->randomElement([
                'Wild capture', 'Purchased package', 'Split', 'Nucleus', 'Swarms'
            ]),
            'queen_status'           => $this->faker->optional()->randomElement([
                'present', 'absent', 'queenless', 'unknown'
            ]),
            'status'                 => $this->faker->randomElement([
                'active', 'active', 'active', 'inactive', 'under_inspection', 'queenless', 'absconded'
            ]),
            'gps_latitude'           => $this->faker->optional()->latitude(-90, 90),
            'gps_longitude'          => $this->faker->optional()->longitude(-180, 180),
            'gps_accuracy_meters'    => $this->faker->optional()->numberBetween(1, 100),

            // Fixed line – now safely handles null with ?->
            'last_inspection_date'   => $this->faker->optional()
                                        ->dateTimeBetween('-1 year', 'now')
                                        ?->format('Y-m-d'),
        ];
    }

    public function forApiary(Apiary|int $apiary): static
    {
        return $this->state(fn (array $attributes) => [
            'apiary_id' => $apiary instanceof Apiary ? $apiary->id : $apiary,
        ]);
    }

    public function healthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'active',
            'queen_status' => 'present',
        ]);
    }

    public function queenless(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'queenless',
            'queen_status' => 'absent',
        ]);
    }
}