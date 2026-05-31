<?php

namespace Database\Factories;

use App\Models\ExamExampleSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamExampleSource>
 */
class ExamExampleSourceFactory extends Factory
{
    protected $model = ExamExampleSource::class;

    public function definition(): array
    {
        return [
            'source_key' => 'source.'.$this->faker->unique()->lexify('????????????'),
            'exam_family' => 'telc',
            'exam_code' => 'telc-b2',
            'variant' => 'allgemein',
            'level' => 'B2',
            'source_type' => 'internal_curated',
            'title' => $this->faker->sentence(3),
            'author_or_publisher' => 'Zertify',
            'source_path' => 'database/examples/catalog.json',
            'language' => 'de',
            'is_canonical_structure_source' => false,
            'is_generation_reference' => true,
            'do_not_publish_directly' => true,
            'metadata' => [],
        ];
    }
}
