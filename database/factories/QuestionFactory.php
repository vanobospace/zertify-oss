<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'topic' => fake()->sentence(3),
            'is_active' => true,
            'content' => [
                'text' => 'Test question with {{gap_1}} and {{gap_2}} gaps.',
                'options' => [
                    'gap_1' => ['option_a', 'option_b', 'option_c'],
                    'gap_2' => ['choice_x', 'choice_y', 'choice_z'],
                ],
                'correct' => [
                    'gap_1' => 'option_b',
                    'gap_2' => 'choice_y',
                ],
                'explanation' => [
                    'gap_1' => 'Explanation for gap 1.',
                    'gap_2' => 'Explanation for gap 2.',
                ],
            ],
            'points' => fake()->numberBetween(1, 5),
            'order' => fake()->numberBetween(0, 10),
            'audio_voice_preset' => Question::AUDIO_VOICE_PRESET_NEWS_FEMALE,
            'audio_style_preset' => Question::AUDIO_STYLE_PRESET_CLEAN,
        ];
    }
}
