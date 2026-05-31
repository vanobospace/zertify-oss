<?php

use App\Filament\Resources\QuestionResource;
use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\QuestionResource\Pages\EditQuestion;
use App\Jobs\GenerateListeningAudioJob;
use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionGenerationTheme;
use App\Models\User;
use App\Services\GeminiService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function authenticateAdmin(): User
{
    $user = User::factory()->admin()->create([
        'email' => 'admin@zertify.app',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    test()->actingAs($user);

    return $user;
}

function fakeGeneratedQuestion(string $format, string $difficulty): array
{
    if ($format === 'listening_segmented_true_false') {
        return [
            'topic' => 'Stadtnachrichten aktuell',
            'difficulty' => $difficulty,
            'content' => [
                'format' => 'listening_segmented_true_false',
                'instructions' => 'Sie hören nun eine Nachrichtensendung. Dazu sollen Sie fünf Aufgaben lösen. Sie hören die Nachrichtensendung nur einmal.',
                'audio' => [
                    'title' => 'Stadtnachrichten am Abend',
                    'audio_notes' => 'Nachrichtensendung mit neutraler Sprecherstimme und fünf klar getrennten Meldungen aus den Regionen.',
                ],
                'intro' => [
                    'text' => 'Guten Tag. Sie hören jetzt fünf Meldungen aus den Regionen.',
                    'voice_profile' => 'anchor_main',
                ],
                'segments' => [
                    ['id' => 'segment_1', 'number' => 1, 'voice_profile' => 'news_main', 'segment_text' => 'Der Wochenmarkt findet morgen wegen Regens in der Stadthalle statt.', 'statement_id' => 'statement_1', 'statement_text' => 'Der Wochenmarkt ist morgen in einer Halle.', 'correct_answer' => 'true', 'reason' => 'Die Stadthalle wird ausdrücklich genannt.', 'evidence' => 'in der Stadthalle'],
                    ['id' => 'segment_2', 'number' => 2, 'voice_profile' => 'news_main', 'segment_text' => 'Die Buslinie 7 fährt wegen einer Baustelle erst ab Dienstag wieder normal.', 'statement_id' => 'statement_2', 'statement_text' => 'Die Buslinie 7 fährt schon heute wieder normal.', 'correct_answer' => 'false', 'reason' => 'Normalbetrieb erst ab Dienstag.', 'evidence' => 'erst ab Dienstag wieder normal'],
                    ['id' => 'segment_3', 'number' => 3, 'voice_profile' => 'news_main', 'segment_text' => 'Im Hallenbad beginnt am Freitag ein zusätzlicher Schwimmkurs für Erwachsene.', 'statement_id' => 'statement_3', 'statement_text' => 'Ab Freitag gibt es einen weiteren Schwimmkurs für Erwachsene.', 'correct_answer' => 'true', 'reason' => 'Ein zusätzlicher Kurs wird genannt.', 'evidence' => 'zusätzlicher Schwimmkurs'],
                    ['id' => 'segment_4', 'number' => 4, 'voice_profile' => 'news_main', 'segment_text' => 'Für das Stadtfest am Samstag werden noch Helfer gesucht.', 'statement_id' => 'statement_4', 'statement_text' => 'Für das Stadtfest werden keine weiteren Helfer benötigt.', 'correct_answer' => 'false', 'reason' => 'Es werden noch Helfer gesucht.', 'evidence' => 'noch Helfer gesucht'],
                    ['id' => 'segment_5', 'number' => 5, 'voice_profile' => 'news_main', 'segment_text' => 'Die Fahrradwerkstatt am Bahnhof öffnet in dieser Woche erst um zehn Uhr.', 'statement_id' => 'statement_5', 'statement_text' => 'Die Fahrradwerkstatt öffnet diese Woche später als sonst.', 'correct_answer' => 'true', 'reason' => 'Sie öffnet erst um zehn Uhr.', 'evidence' => 'erst um zehn Uhr'],
                ],
                'transcript' => implode("\n\n", [
                    'Guten Tag. Sie hören jetzt fünf Meldungen aus den Regionen.',
                    'Der Wochenmarkt findet morgen wegen Regens in der Stadthalle statt.',
                    'Die Buslinie 7 fährt wegen einer Baustelle erst ab Dienstag wieder normal.',
                    'Im Hallenbad beginnt am Freitag ein zusätzlicher Schwimmkurs für Erwachsene.',
                    'Für das Stadtfest am Samstag werden noch Helfer gesucht.',
                    'Die Fahrradwerkstatt am Bahnhof öffnet in dieser Woche erst um zehn Uhr.',
                ]),
                'statements' => [
                    ['id' => 'statement_1', 'number' => 1, 'text' => 'Der Wochenmarkt ist morgen in einer Halle.'],
                    ['id' => 'statement_2', 'number' => 2, 'text' => 'Die Buslinie 7 fährt schon heute wieder normal.'],
                    ['id' => 'statement_3', 'number' => 3, 'text' => 'Ab Freitag gibt es einen weiteren Schwimmkurs für Erwachsene.'],
                    ['id' => 'statement_4', 'number' => 4, 'text' => 'Für das Stadtfest werden keine weiteren Helfer benötigt.'],
                    ['id' => 'statement_5', 'number' => 5, 'text' => 'Die Fahrradwerkstatt öffnet diese Woche später als sonst.'],
                ],
                'correct' => [
                    'statement_1' => 'true',
                    'statement_2' => 'false',
                    'statement_3' => 'true',
                    'statement_4' => 'false',
                    'statement_5' => 'true',
                ],
                'explanation' => [
                    'statement_1' => ['correct_answer' => 'true', 'reason' => 'Die Stadthalle wird ausdrücklich genannt.', 'evidence' => 'in der Stadthalle'],
                    'statement_2' => ['correct_answer' => 'false', 'reason' => 'Normalbetrieb erst ab Dienstag.', 'evidence' => 'erst ab Dienstag wieder normal'],
                    'statement_3' => ['correct_answer' => 'true', 'reason' => 'Ein zusätzlicher Kurs wird genannt.', 'evidence' => 'zusätzlicher Schwimmkurs'],
                    'statement_4' => ['correct_answer' => 'false', 'reason' => 'Es werden noch Helfer gesucht.', 'evidence' => 'noch Helfer gesucht'],
                    'statement_5' => ['correct_answer' => 'true', 'reason' => 'Sie öffnet erst um zehn Uhr.', 'evidence' => 'erst um zehn Uhr'],
                ],
            ],
            'word_count' => 332,
        ];
    }

    if ($format === 'listening_short_true_false') {
        return [
            'topic' => 'Stadtmagazin am Morgen',
            'difficulty' => $difficulty,
            'content' => [
                'format' => 'listening_short_true_false',
                'instructions' => 'Sie hören eine kurze Nachrichtensendung. Entscheiden Sie bei den Aussagen, ob sie richtig oder falsch sind.',
                'audio' => [
                    'title' => 'Stadtmagazin am Morgen',
                    'audio_notes' => 'Kurze Nachrichtensendung mit neutraler Stimme und klar getrennten Meldungen.',
                ],
                'transcript' => 'Guten Morgen. Der Flohmarkt am Wochenende wird wegen des Wetters in die Markthalle verlegt. Die neue Fahrradbrücke bleibt noch bis Montag geschlossen. Für das Sommerkonzert im Park werden zusätzliche Sitzplätze aufgebaut. Im Jugendzentrum startet der neue Programmierkurs bereits morgen. Außerdem sucht die Stadt Freiwillige für eine Pflanzaktion am Samstag.',
                'statements' => [
                    ['id' => 'statement_1', 'number' => 1, 'text' => 'Der Flohmarkt findet in einer Halle statt.'],
                    ['id' => 'statement_2', 'number' => 2, 'text' => 'Die Fahrradbrücke ist schon wieder offen.'],
                    ['id' => 'statement_3', 'number' => 3, 'text' => 'Für das Sommerkonzert werden zusätzliche Sitzplätze aufgebaut.'],
                    ['id' => 'statement_4', 'number' => 4, 'text' => 'Der Programmierkurs startet erst nächste Woche.'],
                    ['id' => 'statement_5', 'number' => 5, 'text' => 'Die Stadt sucht Freiwillige für Samstag.'],
                ],
                'correct' => [
                    'statement_1' => 'true',
                    'statement_2' => 'false',
                    'statement_3' => 'true',
                    'statement_4' => 'false',
                    'statement_5' => 'true',
                ],
                'explanation' => [
                    'statement_1' => [
                        'correct_answer' => 'true',
                        'reason' => 'Die Meldung nennt ausdrücklich die Verlegung in die Markthalle.',
                        'evidence' => 'Im Audio fällt die Formulierung „in die Markthalle verlegt“.',
                    ],
                    'statement_2' => [
                        'correct_answer' => 'false',
                        'reason' => 'Die Brücke bleibt noch bis Montag geschlossen.',
                        'evidence' => 'Im Audio steht „bleibt noch bis Montag geschlossen“.',
                    ],
                    'statement_3' => [
                        'correct_answer' => 'true',
                        'reason' => 'Die Meldung erwähnt zusätzliche Sitzplätze für das Konzert.',
                        'evidence' => 'Im Audio wird der Aufbau zusätzlicher Sitzplätze genannt.',
                    ],
                    'statement_4' => [
                        'correct_answer' => 'false',
                        'reason' => 'Der Kurs beginnt bereits morgen.',
                        'evidence' => 'Im Audio steht „bereits morgen“.',
                    ],
                    'statement_5' => [
                        'correct_answer' => 'true',
                        'reason' => 'Die Stadt sucht Freiwillige für die Pflanzaktion am Samstag.',
                        'evidence' => 'Im Audio werden „Freiwillige“ und „am Samstag“ direkt genannt.',
                    ],
                ],
            ],
            'word_count' => 53,
        ];
    }

    $correct = [];
    $explanation = [];
    $options = [];
    $ruleTypes = [
        'weil' => 'Konjunktion',
        'obwohl' => 'Konjunktion',
        'damit' => 'Konjunktion',
        'wobei' => 'Konjunktion',
        'sodass' => 'Konjunktion',
        'denn' => 'Konjunktion',
        'wenn' => 'Konjunktion',
        'indem' => 'Konjunktion',
        'was' => 'Fragepronomen',
        'als' => 'Konjunktion',
        'da' => 'Konjunktion',
        'dass' => 'Konjunktion',
        'ueber' => 'Präposition',
        'fuer' => 'Präposition',
        'auf' => 'Präposition',
    ];

    for ($i = 1; $i <= 10; $i++) {
        $gapId = "gap_{$i}";
        $answer = $format === 'shared_pool'
            ? [
                1 => 'weil',
                2 => 'obwohl',
                3 => 'damit',
                4 => 'wobei',
                5 => 'sodass',
                6 => 'denn',
                7 => 'wenn',
                8 => 'indem',
                9 => 'was',
                10 => 'als',
            ][$i]
            : [
                1 => 'weil',
                2 => 'da',
                3 => 'sodass',
                4 => 'obwohl',
                5 => 'damit',
                6 => 'wenn',
                7 => 'dass',
                8 => 'ueber',
                9 => 'fuer',
                10 => 'auf',
            ][$i];
        $correct[$gapId] = $answer;
        $explanation[$gapId] = [
            'answer' => $answer,
            'rule_type' => $ruleTypes[$answer] ?? 'Grammatik',
            'reason' => "Hier passt {$answer}, weil die Satzstruktur genau diese Verbindung verlangt.",
            'pattern' => '',
            'contrast' => "Der Distraktor alternative{$i}a passt hier nicht, weil er eine andere Funktion hat.",
            'example' => "Ich nutze {$answer} in diesem kurzen Beispielsatz.",
        ];

        if ($format !== 'shared_pool') {
            $options[$gapId] = [$answer, "alternative{$i}a", "alternative{$i}b"];
        }
    }

    $content = $format === 'shared_pool'
        ? [
            'format' => 'shared_pool',
            'text' => implode("\n\n", [
                'Digitale Gewohnheiten veraendern den Alltag vieler Menschen tiefgreifend, {{gap_1}} staendige Erreichbarkeit heute fast in jedem Beruf erwartet wird. Viele Beschaeftigte schaetzen die neuen Moeglichkeiten, {{gap_2}} sie gleichzeitig merken, wie schnell klare Grenzen zwischen Arbeit und Freizeit verschwinden koennen. Unternehmen fuehren deshalb bewusst Regeln ein, {{gap_3}} Teams konzentrierter arbeiten und technische Hilfsmittel sinnvoll eingesetzt werden.',
                'Auch im privaten Leben wird genauer beobachtet, wie stark Bildschirme den Tagesrhythmus beeinflussen. Manche Familien vereinbaren handyfreie Zeiten beim Essen, {{gap_4}} sie festgestellt haben, dass Gespraeche dadurch ruhiger und verbindlicher werden. Andere reduzieren Benachrichtigungen, {{gap_5}} der Kopf abends nicht mehr dauernd bei neuen Meldungen bleibt. Diese kleinen Entscheidungen wirken oft erstaunlich stark, {{gap_6}} sie direkt an den Gewohnheiten des Alltags ansetzen und nicht erst lange Vorbereitungen verlangen.',
                'Fachleute betonen, dass nachhaltige Veraenderungen nur dann funktionieren, {{gap_7}} neue Regeln realistisch in den eigenen Tagesablauf passen. Wer feste Offline-Phasen schafft, trainiert mehr Aufmerksamkeit, {{gap_8}} er konkrete Situationen anders organisiert und nicht bloss gute Vorsaetze formuliert. Genau das ist der Punkt, {{gap_9}} viele Beratungstexte immer wieder hervorheben: Digitale Balance entsteht nicht durch Verbote, sondern durch bewusste Entscheidungen. Wer diesen Gedanken frueh einuebt, handelt oft gelassener {{gap_10}} in stressigen Phasen alte Muster sofort wieder die Kontrolle uebernehmen.',
            ]),
            'options_pool' => [
                'weil', 'obwohl', 'damit', 'wobei', 'sodass',
                'denn', 'wenn', 'indem', 'was', 'als',
                'doch', 'sondern', 'daher', 'waehrend', 'falls',
            ],
            'correct' => $correct,
            'explanation' => $explanation,
        ]
        : [
            'text' => implode("\n\n", [
                'Sehr geehrte Frau Becker,',
                'ich schreibe Ihnen, {{gap_1}} ich mich nach dem Seminar noch einmal fuer die freundliche Betreuung bedanken moechte. Die Veranstaltung war insgesamt sehr hilfreich, {{gap_2}} die Inhalte klar aufgebaut waren und viele praktische Beispiele gegeben wurden. Besonders motivierend war die offene Atmosphaere im Kurs, {{gap_3}} sich auch ruhigere Teilnehmende schnell eingebracht haben. Dadurch entstand von Anfang an ein Arbeitsklima, in dem man ohne Scheu nachfragen und eigene Erfahrungen aus dem Berufsalltag einbringen konnte.',
                'Sehr positiv fand ich ausserdem, {{gap_4}} es waehrend der einzelnen Module ausreichend Zeit fuer Rueckfragen gab. Bei organisatorischen Themen wurde immer schnell reagiert, {{gap_5}} niemand lange auf eine Antwort warten musste. Man merkte sofort, {{gap_6}} genaue Absprachen im Ablauf die Zusammenarbeit im Kurs deutlich erleichtern. Gerade diese Verlaesslichkeit hat fuer mich einen grossen Unterschied gemacht, weil dadurch auch die Gruppenarbeit ruhig und zielgerichtet verlaufen ist.',
                'Fuer kuenftige Durchgaenge waere es dennoch sinnvoll, {{gap_7}} neue Teilnehmende schon vor Beginn eine kurze Uebersicht zu Material und Ablauf erhalten. Ich waere Ihnen ausserdem dankbar, wenn Sie mich noch {{gap_8}} die geplanten Folgetermine informieren koennten. Das waere auch {{gap_9}} meine berufliche Planung in den kommenden Wochen hilfreich. Zusaetzlich koennte eine kurze Zusammenfassung der wichtigsten Kursinhalte nach jedem Termin dabei helfen, das Gelernte besser zu sichern und spaeter schneller zu wiederholen.',
                'Ich danke Ihnen nochmals fuer die gute Organisation und freue mich schon jetzt {{gap_10}} Ihre Rueckmeldung. Wenn Sie moechten, kann ich Ihnen meine Hinweise auch noch einmal in einer kuerzeren Uebersicht zusammenstellen.',
                'Mit freundlichen Gruessen'."\n".'Max Mustermann',
            ]),
            'options' => $options,
            'correct' => $correct,
            'explanation' => $explanation,
        ];

    return [
        'topic' => 'Generiertes Thema',
        'difficulty' => $difficulty,
        'content' => $content,
        'word_count' => $format === 'shared_pool' ? 280 : 240,
    ];
}

function mockGeminiExpectation(
    string $expectedFormat,
    string $expectedDifficulty,
    string $expectedTopicSeed,
    string $expectedCatalogTitle,
    ?string $expectedGoldenExample = null,
): void {
    $mock = Mockery::mock(GeminiService::class);
    $mock->shouldReceive('generateQuestion')
        ->once()
        ->withArgs(function (array $data) use ($expectedFormat, $expectedDifficulty, $expectedTopicSeed, $expectedCatalogTitle, $expectedGoldenExample): bool {
            return $data['format'] === $expectedFormat
                && $data['difficulty'] === $expectedDifficulty
                && $data['topic_seed'] === $expectedTopicSeed
                && $data['topic_catalog_title'] === $expectedCatalogTitle
                && ($expectedGoldenExample === null || ($data['golden_example'] ?? null) === $expectedGoldenExample);
        })
        ->andReturn(fakeGeneratedQuestion($expectedFormat, $expectedDifficulty));

    app()->instance(GeminiService::class, $mock);
}

describe('question generation action format derivation', function () {
    beforeEach(function () {
        authenticateAdmin();
    });

    it('derives per-gap format from teil 1 on create', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'name' => 'Sprachbausteine Teil 1',
            'slug' => 'sprachbausteine-teil-1',
        ]);
        $theme = QuestionGenerationTheme::factory()->teil1()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Beschwerde ueber einen Kurs',
            'prompt_seed' => 'Halbformelle Beschwerdemail zu einem Sprachkurs.',
            'golden_example' => 'Golden example fuer Teil 1 mit {{gap_1}}.',
            'status' => QuestionGenerationTheme::STATUS_APPROVED,
        ]);

        mockGeminiExpectation('per_gap', 'hard', $theme->prompt_seed, $theme->title, $theme->golden_example);

        $component = Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'hard',
            ])
            ->callAction('generate_with_ai')
            ->assertNotified();

        $q = Question::latest('id')->first();
        expect($q->content['text'] ?? null)->toBeString();
    });

    it('derives shared pool format from teil 2 on create', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'name' => 'Sprachbausteine Teil 2',
            'slug' => 'sprachbausteine-teil-2',
        ]);
        $theme = QuestionGenerationTheme::factory()->teil2()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Digitalisierung im Alltag',
            'prompt_seed' => 'Sachtext ueber Chancen und Probleme digitaler Gewohnheiten.',
            'status' => QuestionGenerationTheme::STATUS_APPROVED,
        ]);

        mockGeminiExpectation('shared_pool', 'easy', $theme->prompt_seed, $theme->title);

        $component = Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'easy',
            ])
            ->callAction('generate_with_ai')
            ->assertNotified();

        $q = Question::latest('id')->first();
        expect($q->content['text'] ?? null)->toBeString();
    });

    it('derives segmented listening format from hoeren teil 1 on create', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
            'name' => 'Hören Teil 1',
            'slug' => 'hoeren-teil-1',
            'type' => 'listening',
        ]);
        $theme = QuestionGenerationTheme::factory()->hoerenTeil1()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Meldungen aus Stadt und Alltag',
            'prompt_seed' => 'Neutral gelesene Nachrichtensendung mit fuenf Meldungen.',
            'golden_example' => 'Guten Morgen. Hier sind die Nachrichten aus der Stadt.',
            'status' => QuestionGenerationTheme::STATUS_APPROVED,
        ]);

        mockGeminiExpectation('listening_segmented_true_false', 'medium', $theme->prompt_seed, $theme->title, $theme->golden_example);

        $component = Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
            ])
            ->callAction('generate_with_ai')
            ->assertNotified();

        $q = Question::latest('id')->first();
        $content = is_array($q->content) ? $q->content : [];

        expect($content['format'])->toBe('listening_segmented_true_false')
            ->and($content['intro']['voice_profile'] ?? null)->toBe('anchor_main')
            ->and($content['segments'] ?? [])->toHaveCount(5)
            ->and($content['transcript'] ?? null)->toBeString()
            ->and($content['statements'] ?? [])->toHaveCount(5)
            ->and($content['correct'] ?? [])->toHaveCount(5);
    });

    it('persists selected audio style preset when creating generated hoeren teil 1 draft', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
            'name' => 'Hören Teil 1',
            'slug' => 'hoeren-teil-1',
            'type' => 'listening',
        ]);
        $theme = QuestionGenerationTheme::factory()->hoerenTeil1()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Meldungen aus Stadt und Alltag',
            'prompt_seed' => 'Neutral gelesene Nachrichtensendung mit fuenf Meldungen.',
            'golden_example' => 'Guten Morgen. Hier sind die Nachrichten aus der Stadt.',
            'status' => QuestionGenerationTheme::STATUS_APPROVED,
        ]);

        mockGeminiExpectation('listening_segmented_true_false', 'medium', $theme->prompt_seed, $theme->title, $theme->golden_example);

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
                'audio_style_preset' => Question::AUDIO_STYLE_PRESET_RADIO_LIGHT,
            ])
            ->callAction('generate_with_ai')
            ->assertNotified()
            ->assertRedirect();

        $question = Question::query()->where('module_id', $module->id)->latest('id')->first();

        expect($question)->not->toBeNull()
            ->and($question?->audio_style_preset)->toBe(Question::AUDIO_STYLE_PRESET_RADIO_LIGHT);
    });

    it('persists selected audio voice preset when creating generated hoeren teil 1 draft', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
            'name' => 'Hören Teil 1',
            'slug' => 'hoeren-teil-1',
            'type' => 'listening',
        ]);
        $theme = QuestionGenerationTheme::factory()->hoerenTeil1()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Meldungen aus Stadt und Alltag',
            'prompt_seed' => 'Neutral gelesene Nachrichtensendung mit fuenf Meldungen.',
            'golden_example' => 'Guten Morgen. Hier sind die Nachrichten aus der Stadt.',
            'status' => QuestionGenerationTheme::STATUS_APPROVED,
        ]);

        mockGeminiExpectation('listening_segmented_true_false', 'medium', $theme->prompt_seed, $theme->title, $theme->golden_example);

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
                'audio_voice_preset' => Question::AUDIO_VOICE_PRESET_NEUTRAL_MALE,
            ])
            ->callAction('generate_with_ai')
            ->assertNotified()
            ->assertRedirect();

        $question = Question::query()->where('module_id', $module->id)->latest('id')->first();

        expect($question)->not->toBeNull()
            ->and($question?->audio_voice_preset)->toBe(Question::AUDIO_VOICE_PRESET_NEUTRAL_MALE);
    });

    it('derives per-gap format from teil 1 on edit', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'name' => 'Sprachbausteine Teil 1',
            'slug' => 'sprachbausteine-teil-1',
        ]);
        $question = Question::factory()->create([
            'module_id' => $module->id,
        ]);
        $theme = QuestionGenerationTheme::factory()->teil1()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Anfrage zu Weiterbildung',
            'prompt_seed' => 'Halbformelle Anfrage zu Organisation und Teilnahmebedingungen.',
            'golden_example' => 'Golden example fuer Edit mit {{gap_1}}.',
            'status' => QuestionGenerationTheme::STATUS_APPROVED,
        ]);

        mockGeminiExpectation('per_gap', 'medium', $theme->prompt_seed, $theme->title, $theme->golden_example);

        $component = Livewire::test(EditQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
            ])
            ->callAction('generate_with_ai')
            ->assertNotified();

        $question->refresh();
        expect($question->content['text'] ?? null)->toBeString();
    });

    it('derives shared pool format from teil 2 on edit', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'name' => 'Sprachbausteine Teil 2',
            'slug' => 'sprachbausteine-teil-2',
        ]);
        $question = Question::factory()->create([
            'module_id' => $module->id,
        ]);
        $theme = QuestionGenerationTheme::factory()->teil2()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Homeoffice und neue Arbeitsmodelle',
            'prompt_seed' => 'Magazintext ueber Veraenderungen der Arbeitswelt.',
            'status' => QuestionGenerationTheme::STATUS_APPROVED,
        ]);

        mockGeminiExpectation('shared_pool', 'hard', $theme->prompt_seed, $theme->title);

        $component = Livewire::test(EditQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'hard',
            ])
            ->callAction('generate_with_ai')
            ->assertNotified();

        $question->refresh();
        expect($question->content['text'] ?? null)->toBeString();
    });

    it('creates a draft question when the ai generation action is called', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'name' => 'Sprachbausteine Teil 1',
            'slug' => 'sprachbausteine-teil-1',
            'default_points' => 1.5,
        ]);
        $theme = QuestionGenerationTheme::factory()->teil1()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Reklamation einer Bestellung',
            'prompt_seed' => 'Alltagsnahe Reklamationsmail zu Lieferung und Kundenservice.',
            'status' => QuestionGenerationTheme::STATUS_APPROVED,
        ]);

        mockGeminiExpectation('per_gap', 'medium', $theme->prompt_seed, $theme->title);

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
            ])
            ->callAction('generate_with_ai')
            ->assertHasNoErrors()
            ->assertNotified()
            ->assertRedirect(); // should redirect to edit

        $question = Question::query()->where('module_id', $module->id)->latest('id')->first();

        expect($question)->not->toBeNull()
            ->and($question->content)->toBeArray()
            ->and($question->content['text'] ?? null)->toBeString()
            ->and($question->content['correct'] ?? null)->toBeArray();
    });

    it('dispatches a generated listening draft after the action is called', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
            'name' => 'Hören Teil 1',
            'slug' => 'hoeren-teil-1',
            'type' => 'listening',
        ]);
        $theme = QuestionGenerationTheme::factory()->hoerenTeil1()->create([
            'exam_slug' => $exam->slug,
            'module_slug' => $module->slug,
            'title' => 'Meldungen aus Stadt und Alltag',
            'prompt_seed' => 'Neutral gelesene Nachrichtensendung mit fuenf Meldungen.',
            'golden_example' => 'Guten Morgen. Hier sind die Nachrichten aus der Stadt.',
            'status' => QuestionGenerationTheme::STATUS_APPROVED,
        ]);

        mockGeminiExpectation('listening_segmented_true_false', 'medium', $theme->prompt_seed, $theme->title, $theme->golden_example);

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
            ])
            ->callAction('generate_with_ai')
            ->assertHasNoErrors()
            ->assertNotified()
            ->assertRedirect(); // redirect to edit

        $question = Question::query()->where('module_id', $module->id)->latest('id')->first();

        expect($question)->not->toBeNull()
            ->and($question->content)->toBeArray()
            ->and($question->content['format'] ?? null)->toBe('listening_segmented_true_false')
            ->and($question->content['intro']['voice_profile'] ?? null)->toBe('anchor_main')
            ->and($question->content['segments'] ?? [])->toHaveCount(5)
            ->and($question->content['transcript'] ?? null)->toBeString();
    });

    it('shows an error when no active theme exists for the selected module', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'slug' => 'sprachbausteine-teil-1',
        ]);

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
            ])
            ->callAction('generate_with_ai')
            ->assertNotified();

        expect(Question::query()->count())->toBe(0);
    });

    it('does not save an invalid teil 2 question when the shared pool misses correct answers', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'name' => 'Sprachbausteine Teil 2',
            'slug' => 'sprachbausteine-teil-2',
        ]);

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
                'topic' => 'Ungueltiger Testfall',
                'content' => json_encode([
                    'format' => 'shared_pool',
                    'text' => 'Absatz eins mit {{gap_1}} und {{gap_2}}.'."\n\n"
                        .'Absatz zwei mit {{gap_3}} und {{gap_4}}.'."\n\n"
                        .'Absatz drei mit {{gap_5}} und {{gap_6}} sowie {{gap_7}}, {{gap_8}}, {{gap_9}} und {{gap_10}}.',
                    'options_pool' => ['aber', 'doch', 'da', 'weil', 'indem', 'sodass', 'sondern', 'obwohl', 'wobei', 'denn', 'als', 'wohingegen', 'damit', 'doch', 'aber'],
                    'correct' => [
                        'gap_1' => 'doch',
                        'gap_2' => 'wenn',
                        'gap_3' => 'sodass',
                        'gap_4' => 'obwohl',
                        'gap_5' => 'was',
                        'gap_6' => 'damit',
                        'gap_7' => 'indem',
                        'gap_8' => 'sondern',
                        'gap_9' => 'indem',
                        'gap_10' => 'dann',
                    ],
                    'explanation' => collect(range(1, 10))->mapWithKeys(
                        fn (int $index): array => [
                            "gap_{$index}" => [
                                'answer' => match ($index) {
                                    1 => 'doch',
                                    2 => 'wenn',
                                    3 => 'sodass',
                                    4 => 'obwohl',
                                    5 => 'was',
                                    6 => 'damit',
                                    7 => 'indem',
                                    8 => 'sondern',
                                    9 => 'indem',
                                    10 => 'dann',
                                },
                                'rule_type' => 'Konjunktion',
                                'reason' => 'Ausreichend konkrete Begründung für diesen Testfall.',
                                'pattern' => '',
                                'contrast' => 'Ein anderer Distraktor passt hier nicht.',
                                'example' => 'Ein kurzer deutscher Beispielsatz für diesen Testfall.',
                            ],
                        ],
                    )->all(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ])
            ->call('create')
            ->assertHasErrors(['content']);

        expect(Question::query()->where('module_id', $module->id)->count())->toBe(0);
    });

    it('does not allow activating a generated question when explanations still need review', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'name' => 'Sprachbausteine Teil 1',
            'slug' => 'sprachbausteine-teil-1',
        ]);

        $payload = fakeGeneratedQuestion('per_gap', 'medium');
        $payload['content']['explanation']['gap_1']['reason'] = '';

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
                'topic' => 'Review needed',
                'content' => json_encode($payload['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => true,
            ])
            ->call('create')
            ->assertHasErrors();

        expect(Question::query()->where('module_id', $module->id)->count())->toBe(0);
    });

    it('allows saving an inactive draft when explanations still need review', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->gapFill()->create([
            'exam_id' => $exam->id,
            'name' => 'Sprachbausteine Teil 1',
            'slug' => 'sprachbausteine-teil-1',
            'default_points' => 1.5,
        ]);

        $payload = fakeGeneratedQuestion('per_gap', 'medium');
        $payload['content']['explanation']['gap_1']['contrast'] = '';

        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'module_id' => $module->id,
                'difficulty' => 'medium',
                'topic' => 'Draft review needed',
                'content' => json_encode($payload['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => false,
            ])
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(QuestionResource::getUrl('index'));

        $question = Question::query()->where('module_id', $module->id)->latest('id')->first();

        expect($question)->not->toBeNull()
            ->and((bool) $question->is_active)->toBeFalse();
    });

    it('queues audio generation for all hoeren modules', function () {
        Queue::fake();

        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $matrix = [
            [
                'module_name' => 'Hören Teil 1',
                'module_slug' => 'hoeren-teil-1',
                'format' => 'listening_segmented_true_false',
                'content' => [
                    'format' => 'listening_segmented_true_false',
                    'instructions' => 'Sie hören eine Nachrichtensendung.',
                    'audio' => ['title' => 'Regionalnachrichten'],
                    'intro' => ['text' => 'Guten Abend.', 'voice_profile' => 'anchor_main'],
                    'segments' => [
                        ['id' => 'segment_1', 'number' => 1, 'voice_profile' => 'news_main', 'segment_text' => 'Meldung eins.'],
                        ['id' => 'segment_2', 'number' => 2, 'voice_profile' => 'news_main', 'segment_text' => 'Meldung zwei.'],
                        ['id' => 'segment_3', 'number' => 3, 'voice_profile' => 'news_main', 'segment_text' => 'Meldung drei.'],
                        ['id' => 'segment_4', 'number' => 4, 'voice_profile' => 'news_main', 'segment_text' => 'Meldung vier.'],
                        ['id' => 'segment_5', 'number' => 5, 'voice_profile' => 'news_main', 'segment_text' => 'Meldung fünf.'],
                    ],
                    'transcript' => 'Guten Abend. Meldung eins. Meldung zwei. Meldung drei. Meldung vier. Meldung fünf.',
                    'statements' => [
                        ['id' => 'statement_1', 'number' => 1, 'text' => 'Aussage eins.'],
                        ['id' => 'statement_2', 'number' => 2, 'text' => 'Aussage zwei.'],
                        ['id' => 'statement_3', 'number' => 3, 'text' => 'Aussage drei.'],
                        ['id' => 'statement_4', 'number' => 4, 'text' => 'Aussage vier.'],
                        ['id' => 'statement_5', 'number' => 5, 'text' => 'Aussage fünf.'],
                    ],
                    'correct' => [
                        'statement_1' => 'true',
                        'statement_2' => 'false',
                        'statement_3' => 'true',
                        'statement_4' => 'false',
                        'statement_5' => 'true',
                    ],
                    'explanation' => [
                        'statement_1' => ['correct_answer' => 'true', 'reason' => 'Begründung 1', 'evidence' => 'Beleg 1'],
                        'statement_2' => ['correct_answer' => 'false', 'reason' => 'Begründung 2', 'evidence' => 'Beleg 2'],
                        'statement_3' => ['correct_answer' => 'true', 'reason' => 'Begründung 3', 'evidence' => 'Beleg 3'],
                        'statement_4' => ['correct_answer' => 'false', 'reason' => 'Begründung 4', 'evidence' => 'Beleg 4'],
                        'statement_5' => ['correct_answer' => 'true', 'reason' => 'Begründung 5', 'evidence' => 'Beleg 5'],
                    ],
                ],
            ],
            [
                'module_name' => 'Hören Teil 2',
                'module_slug' => 'hoeren-teil-2',
                'format' => 'listening_long_true_false',
                'content' => [
                    'format' => 'listening_long_true_false',
                    'instructions' => 'Sie hören ein Interview.',
                    'audio' => ['title' => 'Rundfunk-Interview'],
                    'transcript' => "Moderator: Guten Abend.\nGast: Vielen Dank.",
                    'statements' => array_map(
                        fn (int $number): array => ['id' => "statement_{$number}", 'number' => $number, 'text' => "Aussage {$number}."],
                        range(1, 10),
                    ),
                    'correct' => array_combine(
                        array_map(fn (int $number): string => "statement_{$number}", range(1, 10)),
                        array_map(fn (int $number): string => $number % 2 === 0 ? 'false' : 'true', range(1, 10)),
                    ),
                    'explanation' => array_combine(
                        array_map(fn (int $number): string => "statement_{$number}", range(1, 10)),
                        array_map(
                            fn (int $number): array => ['correct_answer' => $number % 2 === 0 ? 'false' : 'true', 'reason' => "Begründung {$number}", 'evidence' => "Beleg {$number}"],
                            range(1, 10),
                        ),
                    ),
                ],
            ],
            [
                'module_name' => 'Hören Teil 3',
                'module_slug' => 'hoeren-teil-3',
                'format' => 'listening_short_true_false',
                'content' => [
                    'format' => 'listening_short_true_false',
                    'instructions' => 'Sie hören fünf kurze Texte.',
                    'audio' => ['title' => 'Kurze Meldungen'],
                    'transcript' => 'Text eins. Text zwei. Text drei. Text vier. Text fünf.',
                    'statements' => [
                        ['id' => 'statement_1', 'number' => 1, 'text' => 'Aussage eins.'],
                        ['id' => 'statement_2', 'number' => 2, 'text' => 'Aussage zwei.'],
                        ['id' => 'statement_3', 'number' => 3, 'text' => 'Aussage drei.'],
                        ['id' => 'statement_4', 'number' => 4, 'text' => 'Aussage vier.'],
                        ['id' => 'statement_5', 'number' => 5, 'text' => 'Aussage fünf.'],
                    ],
                    'correct' => [
                        'statement_1' => 'true',
                        'statement_2' => 'false',
                        'statement_3' => 'true',
                        'statement_4' => 'false',
                        'statement_5' => 'true',
                    ],
                    'explanation' => [
                        'statement_1' => ['correct_answer' => 'true', 'reason' => 'Begründung 1', 'evidence' => 'Beleg 1'],
                        'statement_2' => ['correct_answer' => 'false', 'reason' => 'Begründung 2', 'evidence' => 'Beleg 2'],
                        'statement_3' => ['correct_answer' => 'true', 'reason' => 'Begründung 3', 'evidence' => 'Beleg 3'],
                        'statement_4' => ['correct_answer' => 'false', 'reason' => 'Begründung 4', 'evidence' => 'Beleg 4'],
                        'statement_5' => ['correct_answer' => 'true', 'reason' => 'Begründung 5', 'evidence' => 'Beleg 5'],
                    ],
                ],
            ],
        ];

        foreach ($matrix as $item) {
            $module = Module::factory()->create([
                'exam_id' => $exam->id,
                'name' => $item['module_name'],
                'slug' => $item['module_slug'],
                'type' => 'listening',
            ]);
            $question = Question::factory()->create([
                'module_id' => $module->id,
                'format' => $item['format'],
                'topic' => 'Queued audio test',
                'content' => $item['content'],
            ]);

            Livewire::test(EditQuestion::class, [
                'record' => $question->getKey(),
            ])
                ->callAction('generate_audio')
                ->assertNotified();

            $question->refresh();
            expect($question->generation_mode)->toBe(Question::GENERATION_MODE_AI_AUDIO_GENERATING);

            Queue::assertPushed(GenerateListeningAudioJob::class, function (GenerateListeningAudioJob $job) use ($question): bool {
                return $job->question->is($question)
                    && $job->previousGenerationMode === Question::GENERATION_MODE_MANUAL;
            });
        }

        Queue::assertPushed(GenerateListeningAudioJob::class, 3);
    });

    it('uses dialog voice pair presets for hoeren teil 2 and keeps full style options available', function () {
        $exam = Exam::factory()->create([
            'slug' => 'telc-b2',
        ]);
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
            'name' => 'Hören Teil 2',
            'slug' => 'hoeren-teil-2',
            'type' => 'listening',
        ]);
        $question = Question::factory()->create([
            'module_id' => $module->id,
            'format' => 'listening_long_true_false',
            'topic' => 'Teil 2 presets',
            'is_active' => false,
            'status' => Question::STATUS_DRAFT,
            'content' => [
                'format' => 'listening_long_true_false',
                'audio' => ['title' => 'Interview'],
                'transcript' => "Moderator: Guten Abend.\nGast: Vielen Dank.",
                'statements' => array_map(
                    fn (int $number): array => ['id' => "statement_{$number}", 'number' => $number, 'text' => "Aussage {$number}."],
                    range(1, 10),
                ),
                'correct' => array_combine(
                    array_map(fn (int $number): string => "statement_{$number}", range(1, 10)),
                    array_map(fn (int $number): string => $number % 2 === 0 ? 'false' : 'true', range(1, 10)),
                ),
                'explanation' => array_combine(
                    array_map(fn (int $number): string => "statement_{$number}", range(1, 10)),
                    array_map(
                        fn (int $number): array => ['correct_answer' => $number % 2 === 0 ? 'false' : 'true', 'reason' => "Begründung {$number}", 'evidence' => "Beleg {$number}"],
                        range(1, 10),
                    ),
                ),
            ],
        ]);

        Livewire::test(EditQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->assertFormSet([
                'audio_voice_preset' => Question::AUDIO_VOICE_PRESET_DIALOG_MF,
            ])
            ->fillForm([
                'audio_voice_preset' => Question::AUDIO_VOICE_PRESET_DIALOG_FM,
                'audio_style_preset' => Question::AUDIO_STYLE_PRESET_ROOM_LIGHT,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $question->refresh();

        expect($question->audio_voice_preset)->toBe(Question::AUDIO_VOICE_PRESET_DIALOG_FM)
            ->and($question->audio_style_preset)->toBe(Question::AUDIO_STYLE_PRESET_ROOM_LIGHT);
    });
});
