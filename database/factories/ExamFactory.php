<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

        return [
            'name' => fake()->words(3, true).' '.fake()->randomElement($levels),
            'slug' => fake()->unique()->slug(),
            'level' => fake()->randomElement($levels),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the exam is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
