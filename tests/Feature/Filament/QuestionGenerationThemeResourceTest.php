<?php

use App\Filament\Resources\QuestionGenerationThemes\Pages\ManageQuestionGenerationThemes;
use App\Filament\Resources\QuestionGenerationThemes\QuestionGenerationThemeResource;
use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionGenerationTheme;
use App\Models\User;
use App\Services\GeminiService;
use Filament\Facades\Filament;
use Livewire\Livewire;

function authenticateThemeAdmin(): User
{
    $user = User::factory()->admin()->create([
        'email' => 'themes-admin@zertify.app',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    test()->actingAs($user);

    return $user;
}

function makeLongSegmentedListeningDraft(): array
{
    $intro = 'Guten Morgen. Hier sind die Nachrichten aus den Regionen. Sie hören jetzt fünf ausführlichere Meldungen aus Stadtleben, Verkehr, Gesundheit, Forschung und Veranstaltungen.';

    $segments = [
        ['id' => 'segment_1', 'number' => 1, 'voice_profile' => 'news_main', 'segment_text' => 'Berlin. In mehreren Bezirken startet heute ein neues Programm zur Begrünung von Schulhöfen. Nach Angaben der Senatsverwaltung sollen bis zum Sommer zusätzliche Bäume gepflanzt, schattige Sitzbereiche geschaffen und versiegelte Flächen teilweise geöffnet werden. Die Arbeiten beginnen zunächst an acht Schulen. Lehrkräfte hoffen, dass die Schulhöfe dadurch auch in den warmen Monaten besser genutzt werden können.', 'statement_id' => 'statement_1', 'statement_text' => 'An mehreren Berliner Schulen sollen die Schulhöfe grüner werden.', 'correct_answer' => 'true', 'reason' => 'Die Meldung beschreibt ein Begrünungsprogramm für Schulhöfe.', 'evidence' => 'ein neues Programm zur Begrünung von Schulhöfen'],
        ['id' => 'segment_2', 'number' => 2, 'voice_profile' => 'news_main', 'segment_text' => 'Bonn. Wegen umfangreicher Gleisarbeiten kommt es auf der Strecke zwischen Bonn und Köln noch bis Freitag zu erheblichen Einschränkungen. Mehrere Regionalzüge fallen in den frühen Morgenstunden aus, außerdem müssen Fahrgäste mit zusätzlichen Umstiegen rechnen. Die Bahn empfiehlt, vor der Abfahrt die aktuellen Verbindungen zu prüfen und für Pendlerwege deutlich mehr Zeit einzuplanen als gewöhnlich.', 'statement_id' => 'statement_2', 'statement_text' => 'Zwischen Bonn und Köln läuft der Zugverkehr diese Woche ohne Einschränkungen.', 'correct_answer' => 'false', 'reason' => 'Es wird ausdrücklich von Einschränkungen gesprochen.', 'evidence' => 'noch bis Freitag zu erheblichen Einschränkungen'],
        ['id' => 'segment_3', 'number' => 3, 'voice_profile' => 'news_main', 'segment_text' => 'Freiburg. Das städtische Gesundheitsamt beginnt kommende Woche mit einer neuen Informationskampagne zum besseren Schutz vor Zecken. In Kitas, Schulen und Sportvereinen sollen Eltern und Kinder erfahren, wie man nach Ausflügen in Parks oder Wäldern den Körper richtig kontrolliert. Zusätzlich werden kostenlose Merkblätter verteilt. Hintergrund ist die gestiegene Zahl gemeldeter Zeckenstiche im vergangenen Frühjahr.', 'statement_id' => 'statement_3', 'statement_text' => 'In Freiburg startet demnächst eine Informationskampagne zum Schutz vor Zecken.', 'correct_answer' => 'true', 'reason' => 'Die Meldung kündigt genau diese Kampagne an.', 'evidence' => 'beginnt kommende Woche mit einer neuen Informationskampagne zum besseren Schutz vor Zecken'],
        ['id' => 'segment_4', 'number' => 4, 'voice_profile' => 'news_main', 'segment_text' => 'München. Ein Forschungsteam der Universität hat ein neues Sensorsystem für Fahrradwege vorgestellt. Die Technik soll Schäden auf dem Belag schneller erkennen und den zuständigen Stellen automatisch melden. Nach Angaben der Hochschule können dadurch kleinere Reparaturen früher geplant werden. Die Stadt will das System zunächst auf zwei stark genutzten Strecken testen und danach über eine Ausweitung entscheiden.', 'statement_id' => 'statement_4', 'statement_text' => 'Das neue Münchner Sensorsystem soll Straßenschäden auf Fahrradwegen früh erkennen.', 'correct_answer' => 'true', 'reason' => 'Das Sensorsystem meldet Schäden schneller.', 'evidence' => 'soll Schäden auf dem Belag schneller erkennen'],
        ['id' => 'segment_5', 'number' => 5, 'voice_profile' => 'news_main', 'segment_text' => 'Dresden. Für das Kulturfestival am ersten Maiwochenende sind deutlich mehr Veranstaltungen geplant als im Vorjahr. Neben Konzerten und Lesungen werden auch kostenlose Führungen durch mehrere Museen angeboten. Die Organisatoren rechnen mit vielen Gästen aus anderen Bundesländern. Wer an den Führungen teilnehmen möchte, muss sich jedoch vorab online anmelden, weil die Plätze in den kleineren Gruppen begrenzt sind.', 'statement_id' => 'statement_5', 'statement_text' => 'Für bestimmte Programmpunkte des Dresdner Kulturfestivals ist eine Online-Anmeldung nötig.', 'correct_answer' => 'true', 'reason' => 'Für die Führungen ist eine Anmeldung vorgeschrieben.', 'evidence' => 'muss sich jedoch vorab online anmelden'],
    ];

    return [
        'topic' => 'Stadtmagazin am Morgen',
        'difficulty' => 'medium',
        'content' => [
            'format' => 'listening_segmented_true_false',
            'instructions' => 'Sie hören nun eine Nachrichtensendung. Dazu sollen Sie fünf Aufgaben lösen. Sie hören die Nachrichtensendung nur einmal.',
            'audio' => [
                'title' => 'Stadtmagazin am Morgen',
                'audio_notes' => 'Nachrichtensendung mit neutraler Sprecherstimme und fünf klar getrennten Meldungen aus den Regionen.',
            ],
            'intro' => [
                'text' => $intro,
                'voice_profile' => 'anchor_main',
            ],
            'segments' => $segments,
            'transcript' => implode("\n\n", array_merge([$intro], array_column($segments, 'segment_text'))),
            'statements' => array_map(fn (array $segment): array => [
                'id' => $segment['statement_id'],
                'number' => $segment['number'],
                'text' => $segment['statement_text'],
            ], $segments),
            'correct' => collect($segments)->mapWithKeys(fn (array $segment): array => [
                $segment['statement_id'] => $segment['correct_answer'],
            ])->all(),
            'explanation' => collect($segments)->mapWithKeys(fn (array $segment): array => [
                $segment['statement_id'] => [
                    'correct_answer' => $segment['correct_answer'],
                    'reason' => $segment['reason'],
                    'evidence' => $segment['evidence'],
                ],
            ])->all(),
        ],
        'word_count' => 340,
    ];
}

describe('question generation theme resource', function () {
    beforeEach(function () {
        authenticateThemeAdmin();
    });

    it('loads the manage themes page', function () {
        Livewire::test(ManageQuestionGenerationThemes::class)
            ->assertOk();
    });

    it('creates a curated ai theme from the admin page', function () {
        Livewire::test(ManageQuestionGenerationThemes::class)
            ->callAction('create', [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-1',
                'title' => 'Anfrage zu einer Weiterbildung',
                'prompt_seed' => 'Halbformelle Anfrage zu Kosten, Terminen und Teilnahmebedingungen.',
                'source_label' => 'Official B2 Allgemein review',
                'source_url' => 'https://example.com/source.pdf',
                'notes' => 'Manuell kuratiert nach interner Prüfung.',
                'status' => QuestionGenerationTheme::STATUS_REVIEWED,
                'sort_order' => 15,
                'is_active' => true,
            ])
            ->assertHasNoActionErrors()
            ->assertRedirect(QuestionGenerationThemeResource::getUrl('index'));

        expect(QuestionGenerationTheme::query()->where('title', 'Anfrage zu einer Weiterbildung')->exists())->toBeTrue();
    });

    it('generates a draft question from a theme and stores the preview payload', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'slug' => 'sprachbausteine-teil-1',
        ]);
        $theme = QuestionGenerationTheme::factory()->teil1()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Beschwerde ueber einen Sprachkurs',
            'prompt_seed' => 'Halbformelle Beschwerdemail zu Kursorganisation und Erstattung.',
        ]);

        $mock = Mockery::mock(GeminiService::class);
        $mock->shouldReceive('generateQuestion')
            ->once()
            ->withArgs(function (array $data) use ($theme): bool {
                return $data['difficulty'] === 'medium'
                    && $data['topic_seed'] === $theme->prompt_seed
                    && $data['topic_catalog_title'] === $theme->title
                    && $data['format'] === 'per_gap';
            })
            ->andReturn([
                'topic' => 'Generierter Brief',
                'difficulty' => 'medium',
                'content' => [
                    'text' => 'Brief mit {{gap_1}} und {{gap_2}}.',
                    'options' => [
                        'gap_1' => ['dass', 'ob', 'weil'],
                        'gap_2' => ['jedoch', 'daher', 'denn'],
                    ],
                    'correct' => [
                        'gap_1' => 'dass',
                        'gap_2' => 'jedoch',
                    ],
                    'explanation' => [
                        'gap_1' => [
                            'answer' => 'dass',
                            'rule_type' => 'Konjunktion',
                            'reason' => 'Erklärung',
                            'pattern' => '',
                            'contrast' => '',
                            'example' => '',
                        ],
                        'gap_2' => [
                            'answer' => 'jedoch',
                            'rule_type' => 'Adverb',
                            'reason' => 'Erklärung',
                            'pattern' => '',
                            'contrast' => '',
                            'example' => '',
                        ],
                    ],
                ],
                'word_count' => 244,
            ]);

        app()->instance(GeminiService::class, $mock);

        Livewire::test(ManageQuestionGenerationThemes::class)
            ->callTableAction('generate_draft', $theme, [
                'difficulty' => 'medium',
            ])
            ->assertHasNoTableActionErrors();

        $theme->refresh();

        expect(Question::query()->where('module_id', $module->id)->where('is_active', false)->exists())->toBeTrue()
            ->and($theme->last_preview_payload)->toBeArray()
            ->and($theme->last_preview_payload['generated']['topic'])->toBe('Generierter Brief')
            ->and($theme->last_previewed_at)->not->toBeNull();
    });

    it('generates a segmented listening draft from a hoeren teil 1 theme', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
            'slug' => 'hoeren-teil-1',
            'name' => 'Hören Teil 1',
            'type' => 'listening',
        ]);
        $theme = QuestionGenerationTheme::factory()->hoerenTeil1()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Meldungen aus Stadt und Alltag',
            'prompt_seed' => 'Neutral gelesene Nachrichtensendung mit fuenf klar getrennten Meldungen aus Stadtleben, Service oder Kultur.',
        ]);

        $mock = Mockery::mock(GeminiService::class);
        $mock->shouldReceive('generateQuestion')
            ->once()
            ->withArgs(function (array $data) use ($theme): bool {
                return $data['difficulty'] === 'medium'
                    && $data['topic_seed'] === $theme->prompt_seed
                    && $data['topic_catalog_title'] === $theme->title
                    && $data['format'] === 'listening_segmented_true_false'
                    && $data['module_slug'] === 'hoeren-teil-1';
            })
            ->andReturn(makeLongSegmentedListeningDraft());

        app()->instance(GeminiService::class, $mock);

        Livewire::test(ManageQuestionGenerationThemes::class)
            ->callTableAction('generate_draft', $theme, [
                'difficulty' => 'medium',
            ])
            ->assertHasNoTableActionErrors();

        $question = Question::query()->where('module_id', $module->id)->latest('id')->first();

        expect($question)->not->toBeNull()
            ->and($question->format)->toBe('listening_segmented_true_false')
            ->and($question->content['intro']['voice_profile'] ?? null)->toBe('anchor_main')
            ->and($question->content['segments'] ?? [])->toHaveCount(5)
            ->and($question->content['transcript'] ?? null)->toBeString();
    });
});
