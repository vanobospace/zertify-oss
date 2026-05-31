<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Services\GeminiService;
use App\Services\QuestionGenerationQualityValidator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('questions:backfill-explanations {--question_id=* : Specific question IDs to process} {--module_id= : Only process one module ID} {--force : Overwrite already structured explanations}')]
#[Description('Generate or enrich structured explanations for existing questions')]
class BackfillQuestionExplanations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GeminiService $gemini): int
    {
        $validator = app(QuestionGenerationQualityValidator::class);
        $query = Question::query()
            ->with('module')
            ->orderBy('id');

        $questionIds = array_filter(array_map('intval', (array) $this->option('question_id')));
        $moduleId = $this->option('module_id');
        $force = (bool) $this->option('force');

        if ($questionIds !== []) {
            $query->whereIn('id', $questionIds);
        }

        if ($moduleId) {
            $query->where('module_id', (int) $moduleId);
        }

        $questions = $query->get();

        if ($questions->isEmpty()) {
            $this->warn('No matching questions found.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $review = 0;

        foreach ($questions as $question) {
            if (! $force && $this->hasStructuredExplanations($question->content)) {
                $skipped++;
                $this->line("Skipping question {$question->id}: rich explanations already exist.");

                continue;
            }

            $content = $question->content;
            $content['explanation'] = $gemini->generateExplanations($content, $question->topic);
            $report = $validator->validateGeneratedQuestion([
                'content' => $content,
            ], $this->resolveFormat($question));

            if ($report['errors'] !== []) {
                $review++;
                $this->warn("Skipped question {$question->id}: ".implode(' | ', $report['errors']));

                continue;
            }

            if (($report['explanations_status'] ?? 'failed') !== 'passed') {
                $review++;
                $this->warn("Skipped question {$question->id}: explanations still need editorial review.");

                continue;
            }

            $question->update(['content' => $content]);
            $updated++;
            $this->info("Updated explanations for question {$question->id}.");
        }

        $this->newLine();
        $this->line("Updated: {$updated}");
        $this->line("Skipped: {$skipped}");
        $this->line("Needs review: {$review}");

        return self::SUCCESS;
    }

    private function resolveFormat(Question $question): string
    {
        return str_contains((string) $question->module?->slug, 'teil-2') ? 'shared_pool' : 'per_gap';
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function hasStructuredExplanations(array $content): bool
    {
        $explanations = $content['explanation'] ?? null;

        if (! is_array($explanations) || $explanations === []) {
            return false;
        }

        foreach ($explanations as $explanation) {
            if (! is_array($explanation)) {
                return false;
            }

            if (! isset($explanation['answer'], $explanation['rule_type'], $explanation['reason'])) {
                return false;
            }
        }

        return true;
    }
}
