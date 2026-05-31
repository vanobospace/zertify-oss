<?php

namespace Database\Factories;

use App\Models\QuestionGenerationTheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionGenerationTheme>
 */
class QuestionGenerationThemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_slug' => 'telc-b2',
            'module_slug' => fake()->randomElement(['sprachbausteine-teil-1', 'sprachbausteine-teil-2', 'hoeren-teil-1']),
            'title' => fake()->unique()->sentence(3),
            'prompt_seed' => fake()->sentence(12),
            'source_label' => 'Internal B2 Allgemein catalog',
            'source_url' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
            'status' => QuestionGenerationTheme::STATUS_DRAFT,
            'last_preview_payload' => null,
            'last_previewed_at' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function teil1(): static
    {
        return $this->state(fn (): array => [
            'module_slug' => 'sprachbausteine-teil-1',
        ]);
    }

    public function teil2(): static
    {
        return $this->state(fn (): array => [
            'module_slug' => 'sprachbausteine-teil-2',
        ]);
    }

    public function hoerenTeil1(): static
    {
        return $this->state(fn (): array => [
            'module_slug' => 'hoeren-teil-1',
        ]);
    }
}
