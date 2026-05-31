<?php

namespace Database\Factories;

use App\Models\ExamExample;
use App\Models\ExamExampleSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamExample>
 */
class ExamExampleFactory extends Factory
{
    protected $model = ExamExample::class;

    public function definition(): array
    {
        return [
            'source_id' => ExamExampleSource::factory(),
            'example_key' => 'example.'.$this->faker->unique()->lexify('????????????'),
            'exam_family' => 'telc',
            'exam_code' => 'telc-b2',
            'variant' => 'allgemein',
            'level' => 'B2',
            'module_slug' => 'hoeren-teil-1',
            'part_key' => 'teil-1',
            'task_shape' => 'listening_segmented_true_false',
            'source_type' => 'internal_curated',
            'source_title' => $this->faker->sentence(3),
            'source_author_or_publisher' => 'Zertify',
            'source_path' => 'database/examples/catalog.json',
            'source_page_from' => null,
            'source_page_to' => null,
            'language' => 'de',
            'is_canonical_structure_source' => false,
            'is_generation_reference' => true,
            'title' => $this->faker->sentence(4),
            'raw_text' => $this->faker->paragraphs(3, true),
            'search_text' => $this->faker->paragraphs(3, true),
            'normalized_payload' => ['reference_text' => $this->faker->paragraphs(2, true)],
            'editorial_notes' => [],
            'rights_note' => 'reference_only',
            'tags' => ['starter'],
            'corpus_hash' => hash('sha256', (string) $this->faker->uuid()),
        ];
    }
}
