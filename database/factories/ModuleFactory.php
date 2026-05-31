<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['reading', 'listening', 'writing', 'sprachbausteine'];

        return [
            'exam_id' => Exam::factory(),
            'name' => fake()->words(2, true).' Teil '.fake()->numberBetween(1, 3),
            'slug' => fake()->unique()->slug(),
            'type' => fake()->randomElement($types),
            'default_points' => fake()->randomFloat(1, 1, 5),
        ];
    }

    /**
     * Indicate that the module is of type gap_fill.
     */
    public function gapFill(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sprachbausteine',
        ]);
    }
}
