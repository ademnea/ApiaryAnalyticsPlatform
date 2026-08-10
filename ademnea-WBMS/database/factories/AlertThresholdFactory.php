<?php

namespace Database\Factories;

use App\Models\AlertThreshold;
use App\Models\Hive;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertThresholdFactory extends Factory
{
    protected $model = AlertThreshold::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->randomElement([
                'feed_required_weight_kg',
                'feed_required_honey_stores',
                'critical_event_threshold_placeholder',
                'malfunction_threshold_placeholder',
            ]),
            'value' => $this->faker->randomFloat(2, 0, 100),
            'description' => $this->faker->optional()->sentence(),
            'hive_id' => null,
        ];
    }

    public function forHive(Hive|int $hive): static
    {
        return $this->state(fn (array $attributes) => [
            'hive_id' => $hive instanceof Hive ? $hive->id : $hive,
        ]);
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'hive_id' => null,
        ]);
    }

    public function feedRequired(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'feed_required_weight_kg',
            'value' => '15',
            'description' => 'Minimum hive weight (kg) before a feed_required alert fires.',
        ]);
    }
}
