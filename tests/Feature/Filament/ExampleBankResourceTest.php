<?php

use App\Filament\Resources\ExamExamples\ExamExampleResource;
use App\Filament\Resources\ExamExamples\Pages\ManageExamExamples;
use App\Filament\Resources\ExamExampleSources\ExamExampleSourceResource;
use App\Filament\Resources\ExamExampleSources\Pages\ManageExamExampleSources;
use App\Models\ExamExample;
use App\Models\ExamExampleSource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function (): void {
    $user = User::factory()->admin()->create([
        'email' => 'example-bank-admin@zertify.app',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($user);
});

it('loads the example sources admin page and shows source records', function () {
    $source = ExamExampleSource::factory()->create([
        'title' => 'Synthetic B2 training references',
        'source_key' => 'source.synthetic.b2.reference',
        'source_type' => 'training_book',
    ]);

    Livewire::test(ManageExamExampleSources::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$source]);

    expect(ExamExampleSourceResource::getUrl('index'))->toContain('/admin');
    expect(ExamExampleSourceResource::getNavigationGroup())->toBe('Банк образцов')
        ->and(ExamExampleSourceResource::getNavigationLabel())->toBe('Источники образцов');
});

it('loads the example bank admin page and shows example records', function () {
    $source = ExamExampleSource::factory()->create([
        'title' => 'Synthetic B2 reading and language references',
        'is_canonical_structure_source' => true,
        'metadata' => ['corpus_role' => 'primary'],
    ]);

    $example = ExamExample::factory()->for($source, 'source')->create([
        'title' => 'Fernstudium neben dem Beruf',
        'module_slug' => 'sprachbausteine-teil-1',
        'part_key' => 'teil-1',
        'task_shape' => 'sprachbausteine_per_gap',
        'exam_code' => 'telc-b2',
        'level' => 'B2',
    ]);

    Livewire::test(ManageExamExamples::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$example]);

    expect(ExamExampleResource::getUrl('index'))->toContain('/admin');
    expect(ExamExampleResource::getNavigationGroup())->toBe('Банк образцов')
        ->and(ExamExampleResource::getNavigationLabel())->toBe('Образцы');
});

it('shows selected secondary workbook examples but keeps archived ones hidden from the default admin list', function () {
    $primarySource = ExamExampleSource::factory()->create([
        'title' => 'Synthetic B2 model test',
        'is_canonical_structure_source' => true,
        'metadata' => ['corpus_role' => 'primary'],
    ]);

    $archiveSource = ExamExampleSource::factory()->create([
        'title' => 'Synthetic B2 reading and language references',
        'is_canonical_structure_source' => false,
        'metadata' => [
            'corpus_role' => 'secondary_visible',
            'visible_example_keys' => ['example.visible-workbook'],
        ],
    ]);

    $primaryExample = ExamExample::factory()->for($primarySource, 'source')->create([
        'title' => 'Official Beispiel',
        'is_canonical_structure_source' => true,
    ]);

    $archivedExample = ExamExample::factory()->for($archiveSource, 'source')->create([
        'title' => 'Archived Beispiel',
        'is_canonical_structure_source' => false,
    ]);

    $visibleSecondaryExample = ExamExample::factory()->for($archiveSource, 'source')->create([
        'example_key' => 'example.visible-workbook',
        'title' => 'Visible Workbook Beispiel',
        'is_canonical_structure_source' => false,
    ]);

    Livewire::test(ManageExamExamples::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$primaryExample, $visibleSecondaryExample])
        ->assertCanNotSeeTableRecords([$archivedExample]);

    expect(ExamExampleResource::getNavigationBadge())->toBe('2');
});

it('formats structured lesen teil 3 content for admin preview', function () {
    $source = ExamExampleSource::factory()->create([
        'title' => 'Synthetic B2 reading and language references',
        'is_canonical_structure_source' => true,
        'metadata' => ['corpus_role' => 'primary'],
    ]);

    $example = ExamExample::factory()->for($source, 'source')->create([
        'title' => 'Ausflüge',
        'module_slug' => 'lesen-teil-3',
        'part_key' => 'teil-3',
        'task_shape' => 'reading_situations_matching',
        'normalized_payload' => [
            'situations' => [
                'Sie möchten einen Ganztagesausflug machen.',
                'Sie möchten eine Schiffsreise machen.',
            ],
            'info_texts' => [
                'A' => 'Stadtteilführung für neue Bewohner.',
                'J' => 'Flusskreuzfahrten auf Rhein und Donau.',
            ],
            'correct' => [
                'situation_11' => 'A',
                'situation_12' => 'J',
            ],
        ],
    ]);

    $primaryPreview = ExamExampleResource::formatStructuredSection($example, ['situations']);
    $secondaryPreview = ExamExampleResource::formatStructuredSection($example, ['info_texts', 'correct']);

    expect($primaryPreview)->toContain('SITUATIONS:')
        ->and($primaryPreview)->toContain('1. Sie möchten einen Ganztagesausflug machen.')
        ->and($secondaryPreview)->toContain('INFO TEXTS:')
        ->and($secondaryPreview)->toContain('A Stadtteilführung für neue Bewohner.')
        ->and($secondaryPreview)->toContain('CORRECT:')
        ->and($secondaryPreview)->toContain('situation_11 A');
});

it('formats a full lesen teil 3 exercise with lettered info texts', function () {
    $source = ExamExampleSource::factory()->create([
        'title' => 'Synthetic B2 reading and language references',
        'is_canonical_structure_source' => true,
        'metadata' => ['corpus_role' => 'primary'],
    ]);

    $example = ExamExample::factory()->for($source, 'source')->create([
        'title' => 'Stadtführer',
        'module_slug' => 'lesen-teil-3',
        'part_key' => 'teil-3',
        'task_shape' => 'reading_situations_matching',
        'normalized_payload' => [
            'instructions' => 'Lesen Sie die Situationen und die Info-Texte.',
            'situations' => [
                'Mit einer 10-jährigen Tochter eine Ausstellung besuchen.',
                'Ohne Berufserfahrung zum Stadtführer ausgebildet werden.',
            ],
            'info_texts' => [
                'A' => 'Baden wie vor 2000 Jahren.',
                'K' => 'Ausbildung zum Stadtführer in Koblenz.',
            ],
            'correct' => [
                'situation_11' => 'A',
                'situation_12' => 'K',
            ],
        ],
    ]);

    $preview = ExamExampleResource::formatFullExercisePreview($example);

    expect($preview)->toContain('INSTRUCTIONS:')
        ->and($preview)->toContain('SITUATIONS:')
        ->and($preview)->toContain('11 Mit einer 10-jährigen Tochter eine Ausstellung besuchen.')
        ->and($preview)->toContain('INFO TEXTS:')
        ->and($preview)->toContain('A Baden wie vor 2000 Jahren.')
        ->and($preview)->toContain('K Ausbildung zum Stadtführer in Koblenz.')
        ->and($preview)->toContain('CORRECT:')
        ->and($preview)->toContain('11 A')
        ->and($preview)->toContain('12 K');
});

it('formats a full lesen teil 1 exercise with headings and texts', function () {
    $source = ExamExampleSource::factory()->create([
        'title' => 'Synthetic B2 reading and language references',
        'is_canonical_structure_source' => true,
        'metadata' => ['corpus_role' => 'primary'],
    ]);

    $example = ExamExample::factory()->for($source, 'source')->create([
        'title' => 'Bienen',
        'module_slug' => 'lesen-teil-1',
        'part_key' => 'teil-1',
        'task_shape' => 'reading_matching_headlines',
        'normalized_payload' => [
            'instructions' => 'Lesen Sie die Texte und ordnen Sie die Überschriften zu.',
            'headings' => [
                'A' => 'Bienen in der Stadt',
                'B' => 'Gute Gründe für Honig',
            ],
            'texts' => [
                'Ein Stadtimker erzählt von seinen Bienenvölkern auf dem Dach.',
                'Warum Honig regional gekauft werden sollte.',
            ],
            'correct' => [
                'text_1' => 'A',
                'text_2' => 'B',
            ],
        ],
    ]);

    $preview = ExamExampleResource::formatFullExercisePreview($example);

    expect($preview)->toContain('INSTRUCTIONS:')
        ->and($preview)->toContain('HEADINGS:')
        ->and($preview)->toContain('A Bienen in der Stadt')
        ->and($preview)->toContain('TEXTS:')
        ->and($preview)->toContain('1. Ein Stadtimker erzählt von seinen Bienenvölkern auf dem Dach.')
        ->and($preview)->toContain('CORRECT:')
        ->and($preview)->toContain('1 A');
});

it('formats a full lesen teil 2 exercise with questions and options', function () {
    $source = ExamExampleSource::factory()->create([
        'title' => 'Synthetic B2 reading and language references',
        'is_canonical_structure_source' => true,
        'metadata' => ['corpus_role' => 'primary'],
    ]);

    $example = ExamExample::factory()->for($source, 'source')->create([
        'title' => 'Christa',
        'module_slug' => 'lesen-teil-2',
        'part_key' => 'teil-2',
        'task_shape' => 'reading_article_mc',
        'normalized_payload' => [
            'instructions' => 'Lesen Sie den Text und beantworten Sie die Fragen.',
            'article_title' => 'Christa, der tierische Star',
            'article_text' => 'Christa arbeitet seit vielen Jahren mit Tieren für Filmproduktionen.',
            'questions' => [
                [
                    'prompt' => 'Womit beschäftigt sich Christa beruflich?',
                    'options' => [
                        'A' => 'Sie trainiert Tiere für Filme.',
                        'B' => 'Sie verkauft Tierfutter.',
                        'C' => 'Sie leitet einen Zoo.',
                    ],
                ],
            ],
            'correct' => [
                'question_1' => 'A',
            ],
        ],
    ]);

    $preview = ExamExampleResource::formatFullExercisePreview($example);

    expect($preview)->toContain('ARTICLE TITLE:')
        ->and($preview)->toContain('Christa, der tierische Star')
        ->and($preview)->toContain('ARTICLE TEXT:')
        ->and($preview)->toContain('QUESTIONS:')
        ->and($preview)->toContain('1. Womit beschäftigt sich Christa beruflich?')
        ->and($preview)->toContain('A Sie trainiert Tiere für Filme.')
        ->and($preview)->toContain('CORRECT:')
        ->and($preview)->toContain('1 A');
});

it('formats official lesen teil 2 articles when payload contains multiple article sections', function () {
    $source = ExamExampleSource::factory()->create([
        'title' => 'Synthetic B2 model test',
        'is_canonical_structure_source' => true,
        'metadata' => ['corpus_role' => 'primary'],
    ]);

    $example = ExamExample::factory()->for($source, 'source')->create([
        'title' => 'Freizeitbegriff / Freizeitrituale',
        'module_slug' => 'lesen-teil-2',
        'part_key' => 'teil-2',
        'task_shape' => 'reading_article_mc',
        'normalized_payload' => [
            'instructions' => 'Lesen Sie zuerst die beiden Artikel und lösen Sie dann die Aufgaben.',
            'articles' => [
                [
                    'title' => 'Freizeitbegriff',
                    'body' => 'Das Freizeitverständnis hat sich grundlegend gewandelt.',
                ],
                [
                    'title' => 'Freizeitrituale',
                    'body' => 'Alles hat seine Regeln und Rituale, auch die Freizeit.',
                ],
            ],
            'questions' => [
                [
                    'number' => 6,
                    'prompt' => 'Siebzig Prozent der Bevölkerung meinen, dass Freizeit ...',
                    'options' => [
                        ['label' => 'A', 'text' => '„Freiheit für etwas“ bedeutet.'],
                        ['label' => 'B', 'text' => 'nicht unbedingt positiv besetzt ist.'],
                        ['label' => 'C', 'text' => 'nur dem Ausruhen dienen sollte.'],
                    ],
                ],
            ],
            'correct' => [
                'question_6' => 'a',
            ],
        ],
    ]);

    $preview = ExamExampleResource::formatFullExercisePreview($example);

    expect($preview)->toContain('ARTICLES:')
        ->and($preview)->toContain('Freizeitbegriff')
        ->and($preview)->toContain('Das Freizeitverständnis hat sich grundlegend gewandelt.')
        ->and($preview)->toContain('Freizeitrituale')
        ->and($preview)->toContain('Alles hat seine Regeln und Rituale, auch die Freizeit.')
        ->and($preview)->toContain('QUESTIONS:')
        ->and($preview)->toContain('6 Siebzig Prozent der Bevölkerung meinen, dass Freizeit ...');
});

it('renders source page previews for example records when preview images exist', function () {
    $previewDirectory = public_path('example-bank-previews/test-preview-full');
    File::ensureDirectoryExists($previewDirectory);
    File::put($previewDirectory.'/page-11.png', 'fake-image');

    $source = ExamExampleSource::factory()->create([
        'metadata' => [
            'preview_directory' => 'public/example-bank-previews/test-preview',
        ],
    ]);

    $example = ExamExample::factory()->for($source, 'source')->create([
        'source_page_from' => 11,
        'source_page_to' => 11,
    ])->load('source');

    $html = ExamExampleResource::renderSourcePagePreviews($example);

    expect($html)->toContain('Page 11')
        ->and($html)->toContain('example-bank-previews/test-preview-full/page-11.png');
});

it('prefers the preview directory that actually covers the requested workbook page range', function () {
    $partialDirectory = public_path('example-bank-previews/test-preview-partial');
    $fullDirectory = public_path('example-bank-previews/test-preview-partial-full');

    File::ensureDirectoryExists($partialDirectory);
    File::ensureDirectoryExists($fullDirectory);
    File::put($partialDirectory.'/page-88.png', 'partial-page');
    File::put($fullDirectory.'/page-1.png', 'full-page');

    $source = ExamExampleSource::factory()->create([
        'metadata' => [
            'preview_directory' => 'public/example-bank-previews/test-preview-partial',
            'preview_directories' => [
                'public/example-bank-previews/test-preview-partial',
                'public/example-bank-previews/test-preview-partial-full',
            ],
        ],
    ]);

    $example = ExamExample::factory()->for($source, 'source')->create([
        'source_page_from' => 88,
        'source_page_to' => 88,
    ])->load('source');

    $html = ExamExampleResource::renderSourcePagePreviews($example);

    expect($html)->toContain('Page 88')
        ->and($html)->toContain('example-bank-previews/test-preview-partial/page-88.png')
        ->and($html)->not->toContain('test-preview-partial-full/page-88.png');
});

it('shows a curated diagnostic instead of an empty source preview for internal curated examples', function () {
    $source = ExamExampleSource::factory()->create([
        'source_type' => 'internal_curated',
        'metadata' => [
            'notes' => 'Internal curated example.',
        ],
    ]);

    $example = ExamExample::factory()->for($source, 'source')->create([
        'source_type' => 'internal_curated',
        'source_page_from' => null,
        'source_page_to' => null,
    ])->load('source');

    $html = ExamExampleResource::renderSourcePagePreviews($example);

    expect($html)->toContain('Curated without source pages.');
});
