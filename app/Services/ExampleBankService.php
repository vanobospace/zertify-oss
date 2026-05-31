<?php

namespace App\Services;

use App\Models\ExamExample;
use App\Models\ExamExampleSource;
use App\Models\Module;
use App\Models\QuestionGenerationTheme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExampleBankService
{
    /**
     * @return array{
     *   version:int,
     *   sources:array<int, array<string, mixed>>,
     *   examples:array<int, array<string, mixed>>
     * }
     */
    public function loadCatalog(?string $path = null): array
    {
        $catalogPath = $this->resolveCatalogPath($path);

        if (! File::exists($catalogPath)) {
            throw new \RuntimeException("Example bank catalog not found at [{$catalogPath}].");
        }

        /** @var array{
         *   version:int,
         *   sources:array<int, array<string, mixed>>,
         *   examples:array<int, array<string, mixed>>
         * } $catalog
         */
        $catalog = json_decode((string) File::get($catalogPath), true, flags: JSON_THROW_ON_ERROR);

        return $catalog;
    }

    /**
     * @return array{sources:int, examples:int}
     */
    public function refreshFromCatalog(?string $path = null): array
    {
        $catalog = $this->loadCatalog($path);
        $this->assertCatalogIsValid($catalog);

        return DB::transaction(function () use ($catalog): array {
            $sourcesCount = $this->syncSources($catalog['sources'] ?? []);
            $examplesCount = $this->syncExamples($catalog['examples'] ?? []);
            $this->deleteMissingExamples($catalog['examples'] ?? []);
            $this->deleteMissingSources($catalog['sources'] ?? []);

            return [
                'sources' => $sourcesCount,
                'examples' => $examplesCount,
            ];
        });
    }

    /**
     * @return array{sources:int, examples:int}
     */
    public function mergeManifestIntoCatalog(string $manifestPath, ?string $catalogPath = null): array
    {
        if (! File::exists($manifestPath)) {
            throw new \RuntimeException("Example bank manifest not found at [{$manifestPath}].");
        }

        /** @var array{
         *   sources?:array<int, array<string, mixed>>,
         *   examples?:array<int, array<string, mixed>>
         * } $manifest
         */
        $manifest = json_decode((string) File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $catalog = $this->loadCatalog($catalogPath);

        $sourcesByKey = collect($catalog['sources'] ?? [])
            ->keyBy(fn (array $source): string => (string) $source['source_key']);

        foreach ($manifest['sources'] ?? [] as $source) {
            $sourcesByKey[(string) $source['source_key']] = $source;
        }

        $examplesByKey = collect($catalog['examples'] ?? [])
            ->keyBy(fn (array $example): string => (string) $example['example_key']);

        foreach ($manifest['examples'] ?? [] as $example) {
            $examplesByKey[(string) $example['example_key']] = $example;
        }

        $merged = [
            'version' => (int) ($catalog['version'] ?? 1),
            'sources' => $sourcesByKey->sortKeys()->values()->all(),
            'examples' => $examplesByKey->sortKeys()->values()->all(),
        ];

        $this->assertCatalogIsValid($merged);

        $resolvedPath = $this->resolveCatalogPath($catalogPath);
        File::ensureDirectoryExists(dirname($resolvedPath));
        File::put($resolvedPath, (string) json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return [
            'sources' => count($manifest['sources'] ?? []),
            'examples' => count($manifest['examples'] ?? []),
        ];
    }

    /**
     * @return Collection<int, ExamExample>
     */
    public function retrieveExamples(array $constraints, int $limit = 3): Collection
    {
        $query = ExamExample::query()
            ->with('source')
            ->where('is_generation_reference', true);

        foreach (['exam_family', 'exam_code', 'variant', 'level', 'module_slug', 'part_key', 'task_shape'] as $field) {
            $value = $constraints[$field] ?? null;

            if (filled($value)) {
                $query->where($field, (string) $value);
            }
        }

        if (filled($constraints['source_key'] ?? null)) {
            $query->whereHas('source', fn ($builder) => $builder->where('source_key', (string) $constraints['source_key']));
        }

        foreach ((array) ($constraints['tags'] ?? []) as $tag) {
            $query->whereJsonContains('tags', (string) $tag);
        }

        $primaryQuery = clone $query;

        $this->applyPrimaryCorpusConstraint($primaryQuery);

        $examples = $primaryQuery
            ->orderByDesc('is_canonical_structure_source')
            ->orderBy('source_title')
            ->orderBy('title')
            ->limit($limit)
            ->get();

        if ($examples->isNotEmpty() || ! (bool) ($constraints['allow_secondary_archive'] ?? false)) {
            return $examples;
        }

        return $query
            ->orderByDesc('is_canonical_structure_source')
            ->orderBy('source_title')
            ->orderBy('title')
            ->limit($limit)
            ->get();
    }

    public function buildReferencePackForTheme(QuestionGenerationTheme $theme, Module $module, int $limit = 3): string
    {
        $constraints = app(ExamExampleShapeResolver::class)->constraintsForModule($module);
        $examples = $this->retrieveExamples($constraints, $limit);

        if ($examples->isEmpty()) {
            return '';
        }

        $blocks = $examples
            ->map(function (ExamExample $example): string {
                $referenceText = trim((string) (data_get($example->normalized_payload, 'reference_text') ?? $example->raw_text));

                return implode("\n", array_filter([
                    "Titel: {$example->title}",
                    "Quelle: {$example->source_title}",
                    "Typ: {$example->task_shape}",
                    $referenceText,
                ]));
            })
            ->all();

        $pack = "Zusätzliche Referenzbeispiele aus dem internen Example Bank (nur als Struktur- und Stilhilfe, nicht kopieren):\n\n";
        $pack .= implode("\n\n---\n\n", $blocks);

        return Str::limit($pack, (int) config('example_bank.max_reference_characters', 4000), '');
    }

    public function lintCatalog(?string $path = null): array
    {
        $catalog = $this->loadCatalog($path);
        $errors = $this->catalogErrors($catalog);

        return [
            'passed' => $errors === [],
            'errors' => $errors,
            'sources' => count($catalog['sources'] ?? []),
            'examples' => count($catalog['examples'] ?? []),
        ];
    }

    protected function resolveCatalogPath(?string $path = null): string
    {
        return $path ?: (string) config('example_bank.path');
    }

    protected function applyPrimaryCorpusConstraint(Builder $query): void
    {
        $query->where(function (Builder $builder): void {
            $builder
                ->where('is_canonical_structure_source', true)
                ->orWhereHas('source', function (Builder $sourceQuery): void {
                    $sourceQuery
                        ->where('is_canonical_structure_source', true)
                        ->orWhere('metadata->corpus_role', 'primary');
                });
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $sources
     */
    protected function syncSources(array $sources): int
    {
        foreach ($sources as $sourcePayload) {
            ExamExampleSource::query()->updateOrCreate(
                ['source_key' => (string) $sourcePayload['source_key']],
                [
                    'exam_family' => (string) $sourcePayload['exam_family'],
                    'exam_code' => (string) $sourcePayload['exam_code'],
                    'variant' => $sourcePayload['variant'] ?? null,
                    'level' => $sourcePayload['level'] ?? null,
                    'source_type' => (string) $sourcePayload['source_type'],
                    'title' => (string) $sourcePayload['title'],
                    'author_or_publisher' => $sourcePayload['author_or_publisher'] ?? null,
                    'source_path' => $sourcePayload['source_path'] ?? null,
                    'language' => (string) ($sourcePayload['language'] ?? 'de'),
                    'is_canonical_structure_source' => (bool) ($sourcePayload['is_canonical_structure_source'] ?? false),
                    'is_generation_reference' => (bool) ($sourcePayload['is_generation_reference'] ?? true),
                    'do_not_publish_directly' => (bool) ($sourcePayload['do_not_publish_directly'] ?? true),
                    'metadata' => $sourcePayload['metadata'] ?? [],
                ],
            );
        }

        return count($sources);
    }

    /**
     * @param  array<int, array<string, mixed>>  $examples
     */
    protected function syncExamples(array $examples): int
    {
        foreach ($examples as $examplePayload) {
            $source = ExamExampleSource::query()
                ->where('source_key', (string) $examplePayload['source_key'])
                ->firstOrFail();

            $rawText = trim((string) $examplePayload['raw_text']);
            $searchText = trim((string) ($examplePayload['search_text'] ?? $this->buildSearchText($examplePayload)));

            ExamExample::query()->updateOrCreate(
                ['example_key' => (string) $examplePayload['example_key']],
                [
                    'source_id' => $source->id,
                    'exam_family' => (string) $examplePayload['exam_family'],
                    'exam_code' => (string) $examplePayload['exam_code'],
                    'variant' => $examplePayload['variant'] ?? null,
                    'level' => $examplePayload['level'] ?? null,
                    'module_slug' => (string) $examplePayload['module_slug'],
                    'part_key' => $examplePayload['part_key'] ?? null,
                    'task_shape' => (string) $examplePayload['task_shape'],
                    'source_type' => (string) $examplePayload['source_type'],
                    'source_title' => (string) $examplePayload['source_title'],
                    'source_author_or_publisher' => $examplePayload['source_author_or_publisher'] ?? null,
                    'source_path' => $examplePayload['source_path'] ?? null,
                    'source_page_from' => $examplePayload['source_page_from'] ?? null,
                    'source_page_to' => $examplePayload['source_page_to'] ?? null,
                    'language' => (string) ($examplePayload['language'] ?? 'de'),
                    'is_canonical_structure_source' => (bool) ($examplePayload['is_canonical_structure_source'] ?? false),
                    'is_generation_reference' => (bool) ($examplePayload['is_generation_reference'] ?? true),
                    'title' => (string) $examplePayload['title'],
                    'raw_text' => $rawText,
                    'search_text' => $searchText,
                    'normalized_payload' => $examplePayload['normalized_payload'] ?? [],
                    'editorial_notes' => $examplePayload['editorial_notes'] ?? [],
                    'rights_note' => $examplePayload['rights_note'] ?? null,
                    'tags' => array_values((array) ($examplePayload['tags'] ?? [])),
                    'corpus_hash' => hash('sha256', $rawText.json_encode($examplePayload['normalized_payload'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                ],
            );
        }

        return count($examples);
    }

    /**
     * @param  array<int, array<string, mixed>>  $sources
     */
    protected function deleteMissingSources(array $sources): void
    {
        $sourceKeys = collect($sources)->pluck('source_key')->filter()->all();

        ExamExampleSource::query()
            ->get()
            ->filter(fn (ExamExampleSource $source): bool => ! in_array($source->source_key, $sourceKeys, true))
            ->each
            ->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $examples
     */
    protected function deleteMissingExamples(array $examples): void
    {
        $exampleKeys = collect($examples)->pluck('example_key')->filter()->all();

        ExamExample::query()
            ->get()
            ->filter(fn (ExamExample $example): bool => ! in_array($example->example_key, $exampleKeys, true))
            ->each
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $examplePayload
     */
    protected function buildSearchText(array $examplePayload): string
    {
        return trim(implode(' ', array_filter([
            $examplePayload['title'] ?? null,
            $examplePayload['exam_code'] ?? null,
            $examplePayload['variant'] ?? null,
            $examplePayload['level'] ?? null,
            $examplePayload['module_slug'] ?? null,
            $examplePayload['part_key'] ?? null,
            $examplePayload['task_shape'] ?? null,
            implode(' ', (array) ($examplePayload['tags'] ?? [])),
            $examplePayload['raw_text'] ?? null,
        ])));
    }

    /**
     * @param  array{
     *   version?:int,
     *   sources?:array<int, array<string, mixed>>,
     *   examples?:array<int, array<string, mixed>>
     * }  $catalog
     * @return list<string>
     */
    protected function catalogErrors(array $catalog): array
    {
        $sourceKeys = collect($catalog['sources'] ?? [])->pluck('source_key')->filter()->all();
        $errors = [];

        foreach ($catalog['examples'] ?? [] as $index => $example) {
            $prefix = "Example #{$index}";

            foreach (['example_key', 'source_key', 'exam_family', 'exam_code', 'module_slug', 'task_shape', 'title', 'raw_text'] as $requiredField) {
                if (! filled($example[$requiredField] ?? null)) {
                    $errors[] = "{$prefix} is missing required field [{$requiredField}].";
                }
            }

            if (! in_array((string) ($example['source_key'] ?? ''), $sourceKeys, true)) {
                $errors[] = "{$prefix} references unknown source_key [".($example['source_key'] ?? '').'].';
            }

            foreach ($this->payloadErrorsForExample($example) as $error) {
                $errors[] = "{$prefix} {$error}";
            }
        }

        return $errors;
    }

    /**
     * @param  array{
     *   version?:int,
     *   sources?:array<int, array<string, mixed>>,
     *   examples?:array<int, array<string, mixed>>
     * }  $catalog
     */
    protected function assertCatalogIsValid(array $catalog): void
    {
        $errors = $this->catalogErrors($catalog);

        if ($errors !== []) {
            throw new \RuntimeException("Example bank catalog is invalid:\n- ".implode("\n- ", $errors));
        }
    }

    /**
     * @param  array<string, mixed>  $example
     * @return list<string>
     */
    protected function payloadErrorsForExample(array $example): array
    {
        $payload = (array) ($example['normalized_payload'] ?? []);
        $errors = [];
        $sourceType = (string) ($example['source_type'] ?? '');

        if ($sourceType !== 'internal_curated' && ! filled($example['source_page_from'] ?? null)) {
            $errors[] = 'is missing [source_page_from] for a source-backed example.';
        }

        if ($sourceType !== 'internal_curated' && ! filled($example['source_page_to'] ?? null)) {
            $errors[] = 'is missing [source_page_to] for a source-backed example.';
        }

        if (! filled($payload['reference_text'] ?? null)) {
            $errors[] = 'is missing normalized_payload[reference_text].';
        }

        if ($sourceType === 'internal_curated') {
            return $errors;
        }

        foreach ($this->requiredPayloadKeysForTaskShape((string) ($example['task_shape'] ?? '')) as $key) {
            if (! filled($payload[$key] ?? null)) {
                $errors[] = "is missing normalized_payload[{$key}] for task shape [".($example['task_shape'] ?? '').'].';
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    protected function requiredPayloadKeysForTaskShape(string $taskShape): array
    {
        return match ($taskShape) {
            'reading_matching_headlines' => ['headings', 'texts', 'correct'],
            'reading_article_mc' => ['instructions', 'article_text', 'questions', 'correct'],
            'reading_situations_matching' => ['instructions', 'situations', 'info_texts', 'correct'],
            'sprachbausteine_per_gap' => ['instructions', 'text_with_gaps', 'options_per_gap', 'correct'],
            'sprachbausteine_shared_pool' => ['instructions', 'text_with_gaps', 'options_pool', 'correct'],
            'listening_segmented_true_false' => ['instructions', 'statements', 'correct', 'transcript'],
            'listening_long_true_false' => ['instructions', 'statements', 'correct', 'transcript'],
            'listening_short_true_false' => ['instructions', 'statements', 'segments', 'correct'],
            default => [],
        };
    }
}
