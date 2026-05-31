<?php

use App\Models\Exam;
use App\Models\ExamExample;
use App\Models\ExamExampleSource;
use App\Models\Module;
use App\Models\QuestionGenerationTheme;
use App\Services\ExampleBankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('example_bank.path', storage_path('framework/testing/example-bank-catalog.json'));

    File::ensureDirectoryExists(dirname((string) config('example_bank.path')));
    File::delete((string) config('example_bank.path'));
});

afterEach(function (): void {
    File::delete((string) config('example_bank.path'));
    File::delete(storage_path('framework/testing/example-bank-manifest.json'));
});

it('refreshes the repo corpus into the runtime example bank idempotently', function () {
    writeExampleBankCatalog([
        'version' => 1,
        'sources' => [
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.booklet',
                title: 'telc B2 booklet',
                sourceType: 'official_booklet',
                isCanonicalStructureSource: true,
            ),
            exampleSourcePayload(
                sourceKey: 'source.goethe.a1.workbook',
                examFamily: 'goethe',
                examCode: 'goethe-a1',
                variant: 'allgemein',
                level: 'A1',
                title: 'Goethe A1 workbook',
            ),
        ],
        'examples' => [
            examplePayload(
                exampleKey: 'example.telc-b2.hoeren.teil-1.1',
                sourceKey: 'source.telc.b2.booklet',
                title: 'Nachrichtensendung am Morgen',
                moduleSlug: 'hoeren-teil-1',
                partKey: 'teil-1',
                taskShape: 'listening_segmented_true_false',
                rawText: 'Guten Morgen. Sie hören eine Nachrichtensendung mit fünf Meldungen.',
            ),
            examplePayload(
                exampleKey: 'example.telc-b2.hoeren.teil-2.1',
                sourceKey: 'source.telc.b2.booklet',
                title: 'Interview über Ausbildung',
                moduleSlug: 'hoeren-teil-2',
                partKey: 'teil-2',
                taskShape: 'listening_long_true_false',
                rawText: 'Sie hören jetzt ein Interview.',
            ),
            examplePayload(
                exampleKey: 'example.goethe-a1.lesen.teil-1.1',
                sourceKey: 'source.goethe.a1.workbook',
                examFamily: 'goethe',
                examCode: 'goethe-a1',
                variant: 'allgemein',
                level: 'A1',
                moduleSlug: 'lesen-teil-1',
                partKey: 'teil-1',
                taskShape: 'reading_matching_headlines',
                title: 'Kurze Anzeigen für A1',
                rawText: 'Lesen Sie die kurzen Anzeigen und ordnen Sie zu.',
            ),
        ],
    ]);

    $this->artisan('examples:refresh-index')
        ->assertSuccessful()
        ->expectsOutputToContain('Example bank index refreshed.');

    $this->artisan('examples:refresh-index')
        ->assertSuccessful();

    expect(ExamExampleSource::query()->count())->toBe(2)
        ->and(ExamExample::query()->count())->toBe(3)
        ->and(ExamExample::query()->where('example_key', 'example.telc-b2.hoeren.teil-1.1')->count())->toBe(1)
        ->and(ExamExampleSource::query()->where('source_key', 'source.telc.b2.booklet')->firstOrFail()->is_canonical_structure_source)->toBeTrue();
});

it('retrieves examples with strict structural constraints and keeps levels separated', function () {
    writeExampleBankCatalog([
        'version' => 1,
        'sources' => [
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.booklet',
                title: 'telc B2 booklet',
                sourceType: 'official_booklet',
                isCanonicalStructureSource: true,
            ),
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.training',
                title: 'Training source',
            ),
            exampleSourcePayload(
                sourceKey: 'source.telc.a1.training',
                examCode: 'telc-a1',
                level: 'A1',
                title: 'A1 source',
            ),
        ],
        'examples' => [
            examplePayload(
                exampleKey: 'example.telc-b2.hoeren.teil-1.1',
                sourceKey: 'source.telc.b2.booklet',
                title: 'Passendes Teil 1 Beispiel',
                moduleSlug: 'hoeren-teil-1',
                partKey: 'teil-1',
                taskShape: 'listening_segmented_true_false',
                rawText: 'Nachrichtensendung mit fünf Meldungen.',
                tags: ['official', 'hoeren'],
            ),
            examplePayload(
                exampleKey: 'example.telc-b2.hoeren.teil-2.1',
                sourceKey: 'source.telc.b2.training',
                title: 'Falsches Teil 2 Beispiel',
                moduleSlug: 'hoeren-teil-2',
                partKey: 'teil-2',
                taskShape: 'listening_long_true_false',
                rawText: 'Interview mit einer Fachkraft.',
                tags: ['interview'],
            ),
            examplePayload(
                exampleKey: 'example.telc-a1.hoeren.teil-1.1',
                sourceKey: 'source.telc.a1.training',
                examCode: 'telc-a1',
                level: 'A1',
                title: 'Falsches A1 Beispiel',
                moduleSlug: 'hoeren-teil-1',
                partKey: 'teil-1',
                taskShape: 'listening_segmented_true_false',
                rawText: 'Sehr einfache Durchsage.',
                tags: ['a1'],
            ),
            examplePayload(
                exampleKey: 'example.telc-b2.lesen.teil-1.1',
                sourceKey: 'source.telc.b2.training',
                title: 'Falsches Lesen Beispiel',
                moduleSlug: 'lesen-teil-1',
                partKey: 'teil-1',
                taskShape: 'reading_matching_headlines',
                rawText: 'Ordnen Sie Überschriften zu.',
                tags: ['lesen'],
            ),
        ],
    ]);

    app(ExampleBankService::class)->refreshFromCatalog();

    $examples = app(ExampleBankService::class)->retrieveExamples([
        'exam_family' => 'telc',
        'exam_code' => 'telc-b2',
        'variant' => 'allgemein',
        'level' => 'B2',
        'module_slug' => 'hoeren-teil-1',
        'part_key' => 'teil-1',
        'task_shape' => 'listening_segmented_true_false',
    ], 10);

    expect($examples)->toHaveCount(1)
        ->and($examples->pluck('example_key')->all())->toBe(['example.telc-b2.hoeren.teil-1.1']);
});

it('builds a reference pack for a theme from matching examples only', function () {
    writeExampleBankCatalog([
        'version' => 1,
        'sources' => [
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.booklet',
                title: 'telc B2 booklet',
                sourceType: 'official_booklet',
                isCanonicalStructureSource: true,
            ),
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.training',
                title: 'Training source',
            ),
        ],
        'examples' => [
            examplePayload(
                exampleKey: 'example.telc-b2.hoeren.teil-1.1',
                sourceKey: 'source.telc.b2.booklet',
                title: 'Nachrichtensendung am Mittag',
                moduleSlug: 'hoeren-teil-1',
                partKey: 'teil-1',
                taskShape: 'listening_segmented_true_false',
                rawText: 'Mittagsnachrichten mit fünf Meldungen.',
                referenceText: 'Teil 1 Referenz: eine Nachrichtensendung mit fünf Meldungen aus Stadt und Region.',
            ),
            examplePayload(
                exampleKey: 'example.telc-b2.hoeren.teil-2.1',
                sourceKey: 'source.telc.b2.training',
                title: 'Interview über den Berufseinstieg',
                moduleSlug: 'hoeren-teil-2',
                partKey: 'teil-2',
                taskShape: 'listening_long_true_false',
                rawText: 'Interviewformat.',
                referenceText: 'Teil 2 Referenz: längeres Radiointerview.',
            ),
        ],
    ]);

    app(ExampleBankService::class)->refreshFromCatalog();

    $exam = Exam::factory()->create([
        'slug' => 'telc-b2',
        'name' => 'B2 Allgemein',
        'level' => 'B2',
    ]);

    $module = Module::factory()->for($exam)->create([
        'slug' => 'hoeren-teil-1',
        'name' => 'Hören Teil 1',
        'type' => 'listening',
    ]);

    $theme = QuestionGenerationTheme::factory()->hoerenTeil1()->create([
        'exam_slug' => 'telc-b2',
        'module_slug' => 'hoeren-teil-1',
        'title' => 'Regionalnachrichten',
    ]);

    $pack = app(ExampleBankService::class)->buildReferencePackForTheme($theme, $module, 5);

    expect($pack)->toContain('Zusätzliche Referenzbeispiele aus dem internen Example Bank')
        ->and($pack)->toContain('Nachrichtensendung am Mittag')
        ->and($pack)->toContain('Teil 1 Referenz')
        ->and($pack)->not->toContain('Interview über den Berufseinstieg')
        ->and($pack)->not->toContain('Teil 2 Referenz');
});

it('uses the official primary corpus before secondary archive examples', function () {
    writeExampleBankCatalog([
        'version' => 1,
        'sources' => [
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.booklet',
                title: 'telc B2 booklet',
                sourceType: 'official_booklet',
                isCanonicalStructureSource: true,
            ),
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.training',
                title: 'Training source',
                metadata: ['corpus_role' => 'secondary_archive'],
            ),
        ],
        'examples' => [
            examplePayload(
                exampleKey: 'example.telc-b2.lesen.teil-1.primary',
                sourceKey: 'source.telc.b2.booklet',
                title: 'Primary Lesen Beispiel',
                moduleSlug: 'lesen-teil-1',
                partKey: 'teil-1',
                taskShape: 'reading_matching_headlines',
                rawText: 'Offizielles Beispiel.',
            ),
            examplePayload(
                exampleKey: 'example.telc-b2.lesen.teil-1.secondary',
                sourceKey: 'source.telc.b2.training',
                title: 'Secondary Lesen Beispiel',
                moduleSlug: 'lesen-teil-1',
                partKey: 'teil-1',
                taskShape: 'reading_matching_headlines',
                rawText: 'Archiviertes Buchbeispiel.',
            ),
        ],
    ]);

    app(ExampleBankService::class)->refreshFromCatalog();

    $examples = app(ExampleBankService::class)->retrieveExamples([
        'exam_family' => 'telc',
        'exam_code' => 'telc-b2',
        'variant' => 'allgemein',
        'level' => 'B2',
        'module_slug' => 'lesen-teil-1',
        'part_key' => 'teil-1',
        'task_shape' => 'reading_matching_headlines',
    ], 10);

    expect($examples->pluck('example_key')->all())->toBe(['example.telc-b2.lesen.teil-1.primary']);
});

it('fails lint when a source-backed example is missing required normalized payload fields for its task shape', function () {
    writeExampleBankCatalog([
        'version' => 1,
        'sources' => [
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.booklet',
                title: 'telc B2 booklet',
                sourceType: 'official_booklet',
                isCanonicalStructureSource: true,
            ),
        ],
        'examples' => [
            [
                ...examplePayload(
                    exampleKey: 'example.telc-b2.lesen.teil-3.1',
                    sourceKey: 'source.telc.b2.booklet',
                    title: 'Unvollständiges Teil 3 Beispiel',
                    moduleSlug: 'lesen-teil-3',
                    partKey: 'teil-3',
                    taskShape: 'reading_situations_matching',
                    rawText: 'Unvollständiger Text.',
                ),
                'normalized_payload' => [
                    'reference_text' => 'Only a reference.',
                ],
            ],
        ],
    ]);

    $this->artisan('examples:lint')
        ->assertFailed()
        ->expectsOutputToContain('normalized_payload[instructions]')
        ->expectsOutputToContain('normalized_payload[situations]')
        ->expectsOutputToContain('normalized_payload[info_texts]');
});

it('merges source manifests into the repo corpus without duplicating keys', function () {
    writeExampleBankCatalog([
        'version' => 1,
        'sources' => [
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.booklet',
                title: 'telc B2 booklet',
                sourceType: 'official_booklet',
                isCanonicalStructureSource: true,
            ),
        ],
        'examples' => [
            examplePayload(
                exampleKey: 'example.telc-b2.hoeren.teil-1.1',
                sourceKey: 'source.telc.b2.booklet',
                title: 'Bestehendes Beispiel',
                moduleSlug: 'hoeren-teil-1',
                partKey: 'teil-1',
                taskShape: 'listening_segmented_true_false',
                rawText: 'Bestehender Text.',
            ),
        ],
    ]);

    $manifestPath = storage_path('framework/testing/example-bank-manifest.json');

    File::put($manifestPath, (string) json_encode([
        'sources' => [
            exampleSourcePayload(
                sourceKey: 'source.telc.b2.training',
                title: 'Neue Trainingsquelle',
            ),
        ],
        'examples' => [
            examplePayload(
                exampleKey: 'example.telc-b2.hoeren.teil-1.1',
                sourceKey: 'source.telc.b2.booklet',
                title: 'Bestehendes Beispiel aktualisiert',
                moduleSlug: 'hoeren-teil-1',
                partKey: 'teil-1',
                taskShape: 'listening_segmented_true_false',
                rawText: 'Aktualisierter Text.',
            ),
            examplePayload(
                exampleKey: 'example.telc-b2.sprachbausteine.teil-1.1',
                sourceKey: 'source.telc.b2.training',
                title: 'Neue Sprachbausteine Referenz',
                moduleSlug: 'sprachbausteine-teil-1',
                partKey: 'teil-1',
                taskShape: 'sprachbausteine_per_gap',
                rawText: 'Neue Sprachbausteine Aufgabe.',
            ),
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->artisan('examples:import-source', ['manifest' => $manifestPath])
        ->assertSuccessful()
        ->expectsOutputToContain('Example bank source manifest merged.');

    $catalog = json_decode((string) File::get((string) config('example_bank.path')), true, flags: JSON_THROW_ON_ERROR);

    expect($catalog['sources'])->toHaveCount(2)
        ->and($catalog['examples'])->toHaveCount(2)
        ->and(collect($catalog['examples'])->firstWhere('example_key', 'example.telc-b2.hoeren.teil-1.1')['title'])->toBe('Bestehendes Beispiel aktualisiert')
        ->and(collect($catalog['examples'])->contains(fn (array $example): bool => $example['example_key'] === 'example.telc-b2.sprachbausteine.teil-1.1'))->toBeTrue();
});

/**
 * @param  array{
 *   version:int,
 *   sources:array<int, array<string, mixed>>,
 *   examples:array<int, array<string, mixed>>
 * }  $catalog
 */
function writeExampleBankCatalog(array $catalog): void
{
    File::put(
        (string) config('example_bank.path'),
        (string) json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

/**
 * @return array<string, mixed>
 */
function exampleSourcePayload(
    string $sourceKey,
    string $examFamily = 'telc',
    string $examCode = 'telc-b2',
    ?string $variant = 'allgemein',
    ?string $level = 'B2',
    string $sourceType = 'training_book',
    string $title = 'Training source',
    bool $isCanonicalStructureSource = false,
    array $metadata = [],
): array {
    return [
        'source_key' => $sourceKey,
        'exam_family' => $examFamily,
        'exam_code' => $examCode,
        'variant' => $variant,
        'level' => $level,
        'source_type' => $sourceType,
        'title' => $title,
        'author_or_publisher' => 'Test Publisher',
        'source_path' => 'database/examples/testing.json',
        'language' => 'de',
        'is_canonical_structure_source' => $isCanonicalStructureSource,
        'is_generation_reference' => true,
        'do_not_publish_directly' => true,
        'metadata' => array_merge([
            'notes' => 'Test source',
            'corpus_role' => $isCanonicalStructureSource ? 'primary' : 'secondary_archive',
        ], $metadata),
    ];
}

/**
 * @param  list<string>  $tags
 * @return array<string, mixed>
 */
function examplePayload(
    string $exampleKey,
    string $sourceKey,
    string $title,
    string $moduleSlug,
    string $partKey,
    string $taskShape,
    string $rawText,
    string $examFamily = 'telc',
    string $examCode = 'telc-b2',
    ?string $variant = 'allgemein',
    ?string $level = 'B2',
    array $tags = ['starter'],
    ?string $referenceText = null,
): array {
    return [
        'example_key' => $exampleKey,
        'source_key' => $sourceKey,
        'exam_family' => $examFamily,
        'exam_code' => $examCode,
        'variant' => $variant,
        'level' => $level,
        'module_slug' => $moduleSlug,
        'part_key' => $partKey,
        'task_shape' => $taskShape,
        'source_type' => 'training_book',
        'source_title' => 'Test source title',
        'source_author_or_publisher' => 'Test Publisher',
        'source_path' => 'database/examples/testing.json',
        'source_page_from' => 1,
        'source_page_to' => 1,
        'language' => 'de',
        'is_canonical_structure_source' => false,
        'is_generation_reference' => true,
        'title' => $title,
        'raw_text' => $rawText,
        'search_text' => implode(' ', [$examCode, $level, $moduleSlug, $partKey, $taskShape, $title]),
        'normalized_payload' => normalizedPayloadForShape($taskShape, $referenceText ?? $rawText),
        'editorial_notes' => ['Test note'],
        'rights_note' => 'test_only',
        'tags' => $tags,
    ];
}

/**
 * @return array<string, mixed>
 */
function normalizedPayloadForShape(string $taskShape, string $referenceText): array
{
    return match ($taskShape) {
        'reading_matching_headlines' => [
            'headings' => ['A Überschrift', 'B Überschrift'],
            'texts' => [
                ['id' => 'text_1', 'text' => 'Kurzer Text 1'],
                ['id' => 'text_2', 'text' => 'Kurzer Text 2'],
            ],
            'correct' => [
                'text_1' => 'A',
                'text_2' => 'B',
            ],
            'reference_text' => $referenceText,
        ],
        'reading_article_mc' => [
            'instructions' => 'Lesen Sie den Text und wählen Sie A, B oder C.',
            'article_text' => 'Ein längerer Sachtext.',
            'questions' => ['Frage 1', 'Frage 2'],
            'options' => [
                'question_6' => ['A', 'B', 'C'],
                'question_7' => ['A', 'B', 'C'],
            ],
            'correct' => [
                'question_6' => 'A',
                'question_7' => 'B',
            ],
            'reference_text' => $referenceText,
        ],
        'reading_situations_matching' => [
            'instructions' => 'Lesen Sie die Situationen und die Info-Texte.',
            'situations' => ['Situation 1', 'Situation 2'],
            'info_texts' => [
                'A' => 'Info A',
                'B' => 'Info B',
            ],
            'correct' => [
                'situation_11' => 'A',
                'situation_12' => 'B',
            ],
            'reference_text' => $referenceText,
        ],
        'sprachbausteine_per_gap' => [
            'instructions' => 'Lesen Sie den Text und wählen Sie die passende Lösung.',
            'text_with_gaps' => 'Text mit ___21___ und ___22___.',
            'options_per_gap' => [
                'gap_21' => ['a', 'b', 'c'],
                'gap_22' => ['a', 'b', 'c'],
            ],
            'correct' => [
                'gap_21' => 'a',
                'gap_22' => 'b',
            ],
            'reference_text' => $referenceText,
        ],
        'sprachbausteine_shared_pool' => [
            'instructions' => 'Lesen Sie den Text und wählen Sie aus dem Lösungspool.',
            'text_with_gaps' => 'Text mit ___21___ und ___22___.',
            'options_pool' => [
                'A' => 'Option A',
                'B' => 'Option B',
                'C' => 'Option C',
            ],
            'correct' => [
                'gap_21' => 'A',
                'gap_22' => 'B',
            ],
            'reference_text' => $referenceText,
        ],
        'listening_segmented_true_false' => [
            'instructions' => 'Sie hören eine Nachrichtensendung.',
            'statements' => ['Aussage 1', 'Aussage 2'],
            'transcript' => "Segment 1\n\nSegment 2",
            'correct' => [
                'statement_1' => true,
                'statement_2' => false,
            ],
            'reference_text' => $referenceText,
        ],
        'listening_long_true_false' => [
            'instructions' => 'Sie hören ein Interview.',
            'statements' => ['Aussage 1', 'Aussage 2'],
            'transcript' => 'Längeres Interview.',
            'correct' => [
                'statement_1' => true,
                'statement_2' => false,
            ],
            'reference_text' => $referenceText,
        ],
        'listening_short_true_false' => [
            'instructions' => 'Sie hören fünf kurze Texte.',
            'statements' => ['Aussage 1', 'Aussage 2'],
            'segments' => [
                ['id' => 'segment_1', 'text' => 'Kurzer Text 1'],
                ['id' => 'segment_2', 'text' => 'Kurzer Text 2'],
            ],
            'correct' => [
                'statement_1' => true,
                'statement_2' => false,
            ],
            'reference_text' => $referenceText,
        ],
        default => [
            'reference_text' => $referenceText,
        ],
    };
}
