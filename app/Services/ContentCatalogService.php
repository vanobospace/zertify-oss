<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Models\QuestionGenerationTheme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ContentCatalogService
{
    protected static bool $isSyncing = false;

    public static function isSyncInProgress(): bool
    {
        return self::$isSyncing;
    }

    /**
     * @return array{
     *     version:int,
     *     exams:array<int, array<string, mixed>>,
     *     modules:array<int, array<string, mixed>>,
     *     question_generation_themes:array<int, array<string, mixed>>,
     *     questions:array<int, array<string, mixed>>
     * }
     */
    public function exportCatalog(?string $path = null): array
    {
        if (self::$isSyncing) {
            return [];
        }

        self::$isSyncing = true;

        try {
            DB::transaction(function (): void {
                $this->ensureQuestionContentKeys();
                $this->ensureThemeContentKeys();
            });

            $catalog = [
                'version' => 1,
                'exams' => $this->buildExamPayloads()->all(),
                'modules' => $this->buildModulePayloads()->all(),
                'question_generation_themes' => $this->buildThemePayloads()->all(),
                'questions' => $this->buildQuestionPayloads()->all(),
            ];

            $catalogPath = $this->resolveCatalogPath($path);

            File::ensureDirectoryExists(dirname($catalogPath));
            File::put(
                $catalogPath,
                (string) json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            return $catalog;
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * @return array{
     *     exams:int,
     *     modules:int,
     *     question_generation_themes:int,
     *     questions:int
     * }
     */
    public function refreshFromCatalog(?string $path = null): array
    {
        if (self::$isSyncing) {
            return [
                'exams' => 0,
                'modules' => 0,
                'question_generation_themes' => 0,
                'questions' => 0,
            ];
        }

        $catalogPath = $this->resolveCatalogPath($path);

        if (! File::exists($catalogPath)) {
            throw new \RuntimeException("Content catalog not found at [{$catalogPath}].");
        }

        /** @var array{
         *     version:int,
         *     exams:array<int, array<string, mixed>>,
         *     modules:array<int, array<string, mixed>>,
         *     question_generation_themes:array<int, array<string, mixed>>,
         *     questions:array<int, array<string, mixed>>
         * } $catalog
         */
        $catalog = json_decode((string) File::get($catalogPath), true, flags: JSON_THROW_ON_ERROR);

        self::$isSyncing = true;

        try {
            return DB::transaction(function () use ($catalog): array {
                $syncedExamSlugs = $this->syncExams($catalog['exams'] ?? []);
                $syncedModuleSlugs = $this->syncModules($catalog['modules'] ?? []);
                $themeCount = $this->syncThemes($catalog['question_generation_themes'] ?? []);
                $questionCount = $this->syncQuestions($catalog['questions'] ?? []);

                $this->deleteMissingThemes($catalog['question_generation_themes'] ?? [], $syncedModuleSlugs);
                $this->deleteMissingQuestions($catalog['questions'] ?? [], $syncedModuleSlugs);
                $this->deleteOrphanedAudioAssets();

                return [
                    'exams' => count($syncedExamSlugs),
                    'modules' => count($syncedModuleSlugs),
                    'question_generation_themes' => $themeCount,
                    'questions' => $questionCount,
                ];
            });
        } finally {
            self::$isSyncing = false;
        }
    }

    protected function resolveCatalogPath(?string $path = null): string
    {
        return $path ?: (string) config('content_catalog.path');
    }

    protected function ensureQuestionContentKeys(): void
    {
        Question::query()
            ->whereNull('seed_key')
            ->where(function ($query): void {
                $query->whereNull('content_key')
                    ->orWhere('content_key', '');
            })
            ->orderBy('id')
            ->get()
            ->each(function (Question $question): void {
                $question->forceFill([
                    'content_key' => 'question.'.strtolower((string) Str::ulid()),
                ])->save();
            });
    }

    protected function ensureThemeContentKeys(): void
    {
        QuestionGenerationTheme::query()
            ->where(function ($query): void {
                $query->whereNull('content_key')
                    ->orWhere('content_key', '');
            })
            ->orderBy('id')
            ->get()
            ->each(function (QuestionGenerationTheme $theme): void {
                $theme->forceFill([
                    'content_key' => 'theme.'.strtolower((string) Str::ulid()),
                ])->save();
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildExamPayloads(): Collection
    {
        return Exam::query()
            ->orderBy('slug')
            ->get()
            ->map(fn (Exam $exam): array => [
                'slug' => (string) $exam->slug,
                'name' => (string) $exam->name,
                'level' => (string) $exam->level,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildModulePayloads(): Collection
    {
        return Module::query()
            ->with('exam')
            ->orderBy('slug')
            ->get()
            ->map(fn (Module $module): array => [
                'exam_slug' => (string) $module->exam?->slug,
                'slug' => (string) $module->slug,
                'name' => (string) $module->name,
                'type' => (string) $module->type,
                'default_points' => $module->default_points,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildThemePayloads(): Collection
    {
        return QuestionGenerationTheme::query()
            ->orderBy('exam_slug')
            ->orderBy('module_slug')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (QuestionGenerationTheme $theme): array => [
                'content_key' => (string) $theme->content_key,
                'exam_slug' => (string) $theme->exam_slug,
                'module_slug' => (string) $theme->module_slug,
                'title' => (string) $theme->title,
                'prompt_seed' => (string) $theme->prompt_seed,
                'source_label' => $theme->source_label,
                'source_url' => $theme->source_url,
                'notes' => $theme->notes,
                'status' => $theme->status,
                'is_active' => (bool) $theme->is_active,
                'sort_order' => (int) $theme->sort_order,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildQuestionPayloads(): Collection
    {
        return Question::query()
            ->with(['module.exam', 'audioAsset'])
            ->orderBy('module_id')
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(function (Question $question): array {
                $payload = [
                    'seed_key' => $question->seed_key,
                    'content_key' => $question->content_key,
                    'exam_slug' => (string) $question->module?->exam?->slug,
                    'module_slug' => (string) $question->module?->slug,
                    'topic' => (string) $question->topic,
                    'format' => $question->format,
                    'difficulty' => $question->difficulty,
                    'content' => $question->content,
                    'points' => $question->points,
                    'order' => $question->order,
                    'is_active' => (bool) $question->is_active,
                    'status' => $question->status,
                    'generation_mode' => $question->generation_mode,
                    'source_label' => $question->source_label,
                    'source_url' => $question->source_url,
                    'source_notes' => $question->source_notes,
                    'audio_source_type' => $question->audio_source_type,
                    'audio_external_url' => $question->audio_external_url,
                    'audio_voice_preset' => $question->audio_voice_preset,
                    'audio_style_preset' => $question->audio_style_preset,
                    'audio_asset' => null,
                ];

                if ($question->audio_source_type === Question::AUDIO_SOURCE_ASSET && $question->audioAsset !== null) {
                    $payload['audio_asset'] = [
                        'label' => $question->audioAsset->label,
                        'path' => $question->audioAsset->path,
                        'disk' => $question->audioAsset->disk,
                        'original_name' => $question->audioAsset->original_name,
                        'duration_seconds' => $question->audioAsset->duration_seconds,
                        'is_active' => (bool) $question->audioAsset->is_active,
                        'transcript_hash' => $question->audioAsset->transcript_hash,
                        'generation_metadata' => $question->audioAsset->generation_metadata,
                        'generated_at' => $question->audioAsset->generated_at?->toIso8601String(),
                    ];
                }

                return $payload;
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $examPayloads
     * @return array<int, string>
     */
    protected function syncExams(array $examPayloads): array
    {
        $slugs = [];

        foreach ($examPayloads as $examPayload) {
            $exam = Exam::query()->updateOrCreate(
                ['slug' => (string) $examPayload['slug']],
                [
                    'name' => $examPayload['name'],
                    'level' => $examPayload['level'],
                ]
            );

            $slugs[] = (string) $exam->slug;
        }

        return $slugs;
    }

    /**
     * @param  array<int, array<string, mixed>>  $modulePayloads
     * @return array<int, string>
     */
    protected function syncModules(array $modulePayloads): array
    {
        $slugs = [];

        foreach ($modulePayloads as $modulePayload) {
            $exam = Exam::query()->where('slug', (string) $modulePayload['exam_slug'])->firstOrFail();

            $module = Module::query()->updateOrCreate(
                ['slug' => (string) $modulePayload['slug']],
                [
                    'exam_id' => $exam->id,
                    'name' => $modulePayload['name'],
                    'type' => $modulePayload['type'],
                    'default_points' => $modulePayload['default_points'],
                ]
            );

            $slugs[] = (string) $module->slug;
        }

        return $slugs;
    }

    /**
     * @param  array<int, array<string, mixed>>  $themePayloads
     */
    protected function syncThemes(array $themePayloads): int
    {
        foreach ($themePayloads as $themePayload) {
            $theme = QuestionGenerationTheme::query()
                ->where('content_key', (string) $themePayload['content_key'])
                ->first();

            if ($theme === null) {
                $theme = QuestionGenerationTheme::query()
                    ->where('exam_slug', (string) $themePayload['exam_slug'])
                    ->where('module_slug', (string) $themePayload['module_slug'])
                    ->where('title', (string) $themePayload['title'])
                    ->first();
            }

            $theme ??= new QuestionGenerationTheme;

            $theme->forceFill([
                'content_key' => $themePayload['content_key'],
                'exam_slug' => $themePayload['exam_slug'],
                'module_slug' => $themePayload['module_slug'],
                'title' => $themePayload['title'],
                'prompt_seed' => $themePayload['prompt_seed'],
                'source_label' => $themePayload['source_label'],
                'source_url' => $themePayload['source_url'],
                'notes' => $themePayload['notes'],
                'status' => $themePayload['status'],
                'is_active' => $themePayload['is_active'],
                'sort_order' => $themePayload['sort_order'],
            ])->save();
        }

        return count($themePayloads);
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionPayloads
     */
    protected function syncQuestions(array $questionPayloads): int
    {
        foreach ($questionPayloads as $questionPayload) {
            $module = Module::query()->where('slug', (string) $questionPayload['module_slug'])->firstOrFail();

            $question = $this->findQuestionForSync($questionPayload);
            $question ??= new Question;

            $question->forceFill([
                'module_id' => $module->id,
                'seed_key' => $questionPayload['seed_key'],
                'content_key' => $questionPayload['content_key'],
                'topic' => $questionPayload['topic'],
                'format' => $questionPayload['format'],
                'difficulty' => $questionPayload['difficulty'],
                'content' => $questionPayload['content'],
                'points' => $questionPayload['points'],
                'order' => $questionPayload['order'],
                'is_active' => $questionPayload['is_active'],
                'status' => $questionPayload['status'],
                'generation_mode' => $questionPayload['generation_mode'],
                'source_label' => $questionPayload['source_label'],
                'source_url' => $questionPayload['source_url'],
                'source_notes' => $questionPayload['source_notes'],
                'audio_source_type' => $questionPayload['audio_source_type'],
                'audio_external_url' => $questionPayload['audio_external_url'],
                'audio_voice_preset' => $questionPayload['audio_voice_preset'],
                'audio_style_preset' => $questionPayload['audio_style_preset'],
            ])->save();

            $this->syncQuestionAudioAsset($question, $questionPayload);
        }

        return count($questionPayloads);
    }

    /**
     * @param  array<string, mixed>  $questionPayload
     */
    protected function findQuestionForSync(array $questionPayload): ?Question
    {
        if (filled($questionPayload['seed_key'] ?? null)) {
            return Question::query()->where('seed_key', (string) $questionPayload['seed_key'])->first();
        }

        if (filled($questionPayload['content_key'] ?? null)) {
            return Question::query()->where('content_key', (string) $questionPayload['content_key'])->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $questionPayload
     */
    protected function syncQuestionAudioAsset(Question $question, array $questionPayload): void
    {
        $audioAssetPayload = $questionPayload['audio_asset'] ?? null;

        if ($question->audio_source_type !== Question::AUDIO_SOURCE_ASSET || ! is_array($audioAssetPayload)) {
            $question->forceFill([
                'question_audio_asset_id' => null,
            ])->save();

            return;
        }

        $asset = $question->audioAsset ?? new QuestionAudioAsset;

        $asset->forceFill([
            'label' => $audioAssetPayload['label'] ?? null,
            'path' => $audioAssetPayload['path'] ?? null,
            'disk' => $audioAssetPayload['disk'] ?? 'public',
            'original_name' => $audioAssetPayload['original_name'] ?? null,
            'duration_seconds' => $audioAssetPayload['duration_seconds'] ?? null,
            'is_active' => $audioAssetPayload['is_active'] ?? true,
            'transcript_hash' => $audioAssetPayload['transcript_hash'] ?? null,
            'generation_metadata' => $audioAssetPayload['generation_metadata'] ?? null,
            'generated_at' => $audioAssetPayload['generated_at'] ?? null,
        ])->save();

        $question->forceFill([
            'question_audio_asset_id' => $asset->id,
        ])->save();
    }

    /**
     * @param  array<int, array<string, mixed>>  $themePayloads
     * @param  array<int, string>  $managedModuleSlugs
     */
    protected function deleteMissingThemes(array $themePayloads, array $managedModuleSlugs): void
    {
        $contentKeys = collect($themePayloads)
            ->pluck('content_key')
            ->filter()
            ->values()
            ->all();

        $themeIdsToDelete = QuestionGenerationTheme::query()
            ->whereIn('module_slug', $managedModuleSlugs)
            ->get()
            ->filter(fn (QuestionGenerationTheme $theme): bool => ! filled($theme->content_key) || ! in_array($theme->content_key, $contentKeys, true))
            ->pluck('id');

        if ($themeIdsToDelete->isNotEmpty()) {
            QuestionGenerationTheme::query()
                ->whereIn('id', $themeIdsToDelete)
                ->delete();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionPayloads
     * @param  array<int, string>  $managedModuleSlugs
     */
    protected function deleteMissingQuestions(array $questionPayloads, array $managedModuleSlugs): void
    {
        $moduleIds = Module::query()
            ->whereIn('slug', $managedModuleSlugs)
            ->pluck('id');

        $seedKeys = collect($questionPayloads)
            ->pluck('seed_key')
            ->filter()
            ->values()
            ->all();

        $contentKeys = collect($questionPayloads)
            ->pluck('content_key')
            ->filter()
            ->values()
            ->all();

        $questionIdsToDelete = Question::query()
            ->whereIn('module_id', $moduleIds)
            ->get()
            ->filter(function (Question $question) use ($contentKeys, $seedKeys): bool {
                if (filled($question->seed_key)) {
                    return ! in_array($question->seed_key, $seedKeys, true);
                }

                return ! filled($question->content_key) || ! in_array($question->content_key, $contentKeys, true);
            })
            ->pluck('id');

        if ($questionIdsToDelete->isNotEmpty()) {
            Question::query()
                ->whereIn('id', $questionIdsToDelete)
                ->delete();
        }
    }

    protected function deleteOrphanedAudioAssets(): void
    {
        QuestionAudioAsset::query()
            ->doesntHave('questions')
            ->delete();
    }
}
