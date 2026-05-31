<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionGenerationTheme;
use RuntimeException;

class QuestionGenerationThemeDraftService
{
    public function __construct(
        protected ExampleBankService $exampleBankService,
    ) {}

    /**
     * @return array{generated: array<string, mixed>, module: Module, format: string}
     */
    public function generatePreview(QuestionGenerationTheme $theme, string $difficulty): array
    {
        $module = Module::query()
            ->where('slug', $theme->module_slug)
            ->whereHas('exam', fn ($query) => $query->where('slug', $theme->exam_slug))
            ->with('exam')
            ->first();

        if (! $module) {
            throw new RuntimeException("No module found for theme [{$theme->title}] using {$theme->exam_slug}/{$theme->module_slug}.");
        }

        $format = app(QuestionFormatResolver::class)->resolveForModule($module);
        $goldenExample = trim(implode("\n\n", array_filter([
            $theme->golden_example ?? '',
            $this->exampleBankService->buildReferencePackForTheme(
                $theme,
                $module,
                (int) config('example_bank.default_reference_limit', 3),
            ),
        ])));
        $generated = app(GeminiService::class)->generateQuestion([
            'format' => $format,
            'difficulty' => $difficulty,
            'topic_seed' => $theme->prompt_seed,
            'topic_catalog_title' => $theme->title,
            'golden_example' => $goldenExample,
            'module_slug' => $module->slug,
        ]);

        $theme->forceFill([
            'last_preview_payload' => [
                'difficulty' => $difficulty,
                'format' => $format,
                'generated' => $generated,
            ],
            'last_previewed_at' => now(),
        ])->save();

        return [
            'generated' => $generated,
            'module' => $module,
            'format' => $format,
        ];
    }

    public function generateDraftQuestion(QuestionGenerationTheme $theme, string $difficulty): Question
    {
        $preview = $this->generatePreview($theme, $difficulty);
        /** @var Module $module */
        $module = $preview['module'];
        /** @var array<string, mixed> $generated */
        $generated = $preview['generated'];
        $format = (string) $preview['format'];
        $nextOrder = (int) Question::query()->where('module_id', $module->id)->max('order') + 10;

        return Question::query()->create([
            'module_id' => $module->id,
            'format' => $format,
            'status' => Question::STATUS_DRAFT,
            'generation_mode' => Question::GENERATION_MODE_AI_DRAFT,
            'topic' => (string) ($generated['topic'] ?? $theme->title),
            'difficulty' => (string) ($generated['difficulty'] ?? $difficulty),
            'content' => $generated['content'] ?? [],
            'source_label' => $theme->source_label,
            'source_url' => $theme->source_url,
            'source_notes' => $theme->notes,
            'points' => (int) round((float) ($module->default_points ?? 1)),
            'order' => $nextOrder,
            'is_active' => 0,
        ]);
    }
}
