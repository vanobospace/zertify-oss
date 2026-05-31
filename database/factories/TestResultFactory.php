<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\TestResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestResult>
 */
class TestResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $maxScore = fake()->numberBetween(5, 50);

        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'score' => fake()->numberBetween(0, $maxScore),
            'max_score' => $maxScore,
            'user_answers' => [
                'gap_1' => 'option_b',
                'gap_2' => 'choice_y',
            ],
        ];
    }

    /**
     * Indicate a perfect score.
     */
    public function perfect(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $attributes['max_score'],
        ]);
    }

    /**
     * Indicate a zero score.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => 0,
        ]);
    }
}
