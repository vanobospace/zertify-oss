<?php

use App\Services\GeminiService;
use App\Services\ListeningDraftExplanationGenerationService;
use App\Services\QuestionGenerationQualityValidator;
use Illuminate\Support\Facades\Http;

class FakeGeminiQuestionGenerator extends GeminiService
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $responses;

    /**
     * @var list<string>
     */
    public array $prompts = [];

    /**
     * @var array<string, array<string, string>>
     */
    public array $generatedExplanations;

    /**
     * @var list<array<string, array<string, string>>>
     */
    public array $generatedExplanationResponses = [];

    public int $explanationCalls = 0;

    /**
     * @var list<float>
     */
    public array $timeline = [];

    public int $budgetSeconds = 50;

    public bool $preparedInteractiveBudget = false;

    /**
     * @param  list<array<string, mixed>>  $responses
     * @param  array<string, array<string, string>>  $generatedExplanations
     */
    public function __construct(array $responses, array $generatedExplanations = [], array $generatedExplanationResponses = [])
    {
        $this->responses = $responses;
        $this->generatedExplanations = $generatedExplanations;
        $this->generatedExplanationResponses = $generatedExplanationResponses;
    }

    protected function callGemini(string $prompt): array
    {
        $this->prompts[] = $prompt;

        return array_shift($this->responses) ?? [];
    }

    public function generateExplanations(array $content, string $topic = '', string $qualityRetryHint = ''): array
    {
        $this->explanationCalls++;

        if ($this->generatedExplanationResponses !== []) {
            return array_shift($this->generatedExplanationResponses) ?? [];
        }

        return $this->generatedExplanations;
    }

    protected function currentTime(): float
    {
        if ($this->timeline !== []) {
            return array_shift($this->timeline);
        }

        return 0.0;
    }

    protected function requestBudgetSeconds(): int
    {
        return $this->budgetSeconds;
    }

    protected function prepareInteractiveExecutionBudget(): void
    {
        $this->preparedInteractiveBudget = true;
    }
}

class GeminiHttpClientProbe extends GeminiService
{
    /**
     * @return array<string, mixed>
     */
    public function invokeCallGeminiJson(string $prompt): array
    {
        return $this->callGeminiJson($prompt);
    }
}

/**
 * @return array<string, mixed>
 */
function makePerGapPayload(bool $withStructuredExplanations = true, int $wordMultiplier = 8): array
{
    $textParts = ['Hallo Frau Becker,'];
    $options = [];
    $correct = [];
    $explanations = [];

    for ($i = 1; $i <= 10; $i++) {
        $gapId = "gap_{$i}";
        $correctAnswer = "wort{$i}";
        $options[$gapId] = [$correctAnswer, "falsch{$i}a", "falsch{$i}b"];
        $correct[$gapId] = $correctAnswer;
        $textParts[] = "Im Absatz {$i} erklaere ich die Situation genauer und setze {{".$gapId.'}} in einen realistischen B2-Alltagskontext.';

        $explanations[$gapId] = $withStructuredExplanations
            ? [
                'answer' => $correctAnswer,
                'rule_type' => 'Konjunktion',
                'reason' => "Hier passt {$correctAnswer}, weil der Satz eine konkrete logische Verbindung im lokalen Kontext verlangt.",
                'pattern' => '',
                'contrast' => "Der Distraktor falsch{$i}a passt nicht, weil er hier eine andere Beziehung ausdruecken wuerde.",
                'example' => "Ich sage {$correctAnswer} ganz bewusst in diesem neuen Beispielsatz.",
            ]
            : "Schwache Erklaerung {$i}";
    }

    $filler = str_repeat('Dieser Text liefert ausreichend thematischen Inhalt fuer eine hochwertige telc B2 Allgemein Aufgabe. ', $wordMultiplier);
    $paragraphs = [
        $textParts[0],
        $filler.' '.$textParts[1].' '.$textParts[2].' '.$textParts[3],
        $textParts[4].' '.$textParts[5].' '.$textParts[6],
        $textParts[7].' '.$textParts[8].' '.$textParts[9].' '.$textParts[10],
        'Vielen Dank fuer Ihre Rueckmeldung.'."\n".'Viele Gruesse'."\n".'Anna Meier',
    ];

    return [
        'topic' => 'Generiertes Thema',
        'difficulty' => 'medium',
        'content' => [
            'text' => implode("\n\n", $paragraphs),
            'options' => $options,
            'correct' => $correct,
            'explanation' => $explanations,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function makeBrokenPerGapPayload(): array
{
    $payload = makePerGapPayload();
    $payload['content']['text'] = str_replace('Hallo Frau Becker,', 'Der folgende Text beginnt ohne Anrede.', (string) $payload['content']['text']);
    $payload['content']['text'] = str_replace('Viele Gruesse', 'Abschluss ohne Schlussformel', (string) $payload['content']['text']);

    return $payload;
}

/**
 * @return array<string, mixed>
 */
function makePayloadWithHiddenAntecedentRelativePronoun(): array
{
    $payload = makeSharedPoolPayload();
    $payload['content']['correct']['gap_2'] = 'der';
    $payload['content']['options_pool'][1] = 'der';
    $payload['content']['text'] = 'Viele Erwachsene lernen weiter, weil die eigene Motivation entscheidend ist, {{gap_2}} fuer den Lernerfolg massgeblich bleibt.';
    $payload['content']['explanation']['gap_2'] = [
        'answer' => 'der',
        'rule_type' => 'Relativpronomen',
        'reason' => 'Das Relativpronomen bezieht sich auf einen impliziten Faktor, der hier gemeint ist.',
        'pattern' => '',
        'contrast' => '"die" passt nicht, weil hier eigentlich der versteckte Faktor gemeint ist.',
        'example' => 'Dies ist der Faktor, der fuer den Erfolg wichtig ist.',
    ];

    return $payload;
}

/**
 * @return array<string, mixed>
 */
function makePayloadWithBrokenPassiveRelativeClause(): array
{
    $payload = makePerGapPayload();
    $payload['content']['correct']['gap_3'] = 'die';
    $payload['content']['options']['gap_3'] = ['die', 'denen', 'welche'];
    $payload['content']['text'] = "Sehr geehrte Damen und Herren,\n\nLeider entsprachen die Leistungen nicht den Erwartungen, {{gap_3}} wir in der Broschuere versprochen wurden.\n\nMit freundlichen Gruessen\nAnna Meier";
    $payload['content']['explanation']['gap_3'] = [
        'answer' => 'die',
        'rule_type' => 'Relativpronomen',
        'reason' => 'Das Relativpronomen bezieht sich auf Erwartungen im Plural.',
        'pattern' => '',
        'contrast' => '"denen" passt nicht, weil hier das Subjekt des Relativsatzes gemeint ist.',
        'example' => 'Das sind die Ziele, die uns versprochen wurden.',
    ];

    return $payload;
}

/**
 * @return array<string, mixed>
 */
function makePerGapPayloadWithoutClassicGreeting(): array
{
    $payload = makePerGapPayload();
    $payload['content']['text'] = preg_replace(
        '/^Hallo Frau Becker,/u',
        'Guten Morgen Frau Becker,',
        (string) $payload['content']['text'],
    ) ?? (string) $payload['content']['text'];
    $payload['content']['text'] = str_replace(
        'Vielen Dank fuer Ihre Rueckmeldung.'."\n".'Viele Gruesse'."\n".'Anna Meier',
        "Vielen Dank im Voraus fuer Ihre Antwort.\nIch freue mich auf Ihre Rueckmeldung.\nAnna Meier",
        (string) $payload['content']['text'],
    );

    return $payload;
}

/**
 * @return array<string, mixed>
 */
function makeSharedPoolPayload(int $poolCount = 15, int $wordMultiplier = 9): array
{
    $correct = [];
    $pool = [];
    $explanations = [];
    $paragraphs = [];

    for ($i = 1; $i <= 10; $i++) {
        $gapId = "gap_{$i}";
        $correctAnswer = "ausdruck{$i}";
        $correct[$gapId] = $correctAnswer;
        $pool[] = $correctAnswer;
        $paragraphs[] = "Im Abschnitt {$i} steht {{".$gapId.'}} als passender Ausdruck und verbindet die Aussage mit der vorherigen Beobachtung.';
        $explanations[$gapId] = [
            'answer' => $correctAnswer,
            'rule_type' => 'Kausaladverb',
            'reason' => "Hier passt {$correctAnswer}, weil der Abschnitt eine klare kausale Verknuepfung im Kontext benoetigt.",
            'pattern' => '',
            'contrast' => 'Der Ausdruck ausdruck11 passt hier nicht, weil er keine kausale Folge markiert.',
            'example' => "Es regnet, {$correctAnswer} bleibe ich heute zu Hause.",
        ];
    }

    for ($i = 11; $i <= $poolCount; $i++) {
        $pool[] = "ausdruck{$i}";
    }

    $filler = str_repeat('Der Sachtext beleuchtet ein Thema von allgemeinem Interesse mit mehreren Perspektiven auf B2 Niveau. ', $wordMultiplier);
    $articleParagraphs = [
        $filler.' '.$paragraphs[0].' '.$paragraphs[1].' '.$paragraphs[2],
        $paragraphs[3].' '.$paragraphs[4].' '.$paragraphs[5].' '.$paragraphs[6],
        $paragraphs[7].' '.$paragraphs[8].' '.$paragraphs[9],
    ];

    return [
        'topic' => 'Gesellschaftliches Thema',
        'difficulty' => 'medium',
        'content' => [
            'format' => 'shared_pool',
            'text' => implode("\n\n", $articleParagraphs),
            'options_pool' => $pool,
            'correct' => $correct,
            'explanation' => $explanations,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function makeSharedPoolPayloadWithWeakExplanationChecks(): array
{
    $payload = makeSharedPoolPayload();
    $payload['content']['correct']['gap_1'] = 'ob';
    $payload['content']['options_pool'][0] = 'ob';
    $payload['content']['explanation']['gap_1'] = [
        'answer' => 'ob',
        'rule_type' => 'Interrogativadverb',
        'reason' => 'Hier passt ob, weil eine indirekte Frage eingeleitet wird.',
        'pattern' => 'die Frage, ob ...',
        'contrast' => 'Ein anderer Ausdruck aus dem Pool passt hier nicht.',
        'example' => 'Ich frage mich, ob er morgen kommt.',
    ];
    $payload['content']['explanation']['gap_2'] = [
        'answer' => 'ausdruck2',
        'rule_type' => 'Kausaladverb',
        'reason' => 'Hier passt ausdruck2, weil der Abschnitt eine klare kausale Verknuepfung im Kontext benoetigt.',
        'pattern' => '',
        'contrast' => 'Ein anderer Ausdruck aus dem Pool passt hier nicht.',
        'example' => 'Dieses Beispiel verwendet absichtlich ein anderes Wort.',
    ];

    return $payload;
}

/**
 * @return array<string, mixed>
 */
function makeListeningShortPayload(bool $withExplanations = true): array
{
    $content = [
        'format' => 'listening_short_true_false',
        'instructions' => 'Sie hören eine kurze Nachrichtensendung.',
        'audio' => [
            'title' => 'Stadtmagazin am Morgen',
            'audio_notes' => 'Kurze Nachrichtensendung mit neutraler Stimme und klar getrennten Meldungen.',
        ],
        'transcript' => 'Guten Morgen. Der Flohmarkt am Wochenende wird wegen des Wetters in die Markthalle verlegt. Die neue Fahrradbrücke bleibt noch bis Montag geschlossen. Für das Sommerkonzert im Park werden zusätzliche Sitzplätze aufgebaut. Im Jugendzentrum startet der neue Programmierkurs bereits morgen. Außerdem sucht die Stadt Freiwillige für eine Pflanzaktion am Samstag.',
        'statements' => [
            ['id' => 'statement_1', 'number' => 1, 'text' => 'Der Flohmarkt findet in einer Halle statt.'],
            ['id' => 'statement_2', 'number' => 2, 'text' => 'Die Fahrradbrücke ist schon offen.'],
            ['id' => 'statement_3', 'number' => 3, 'text' => 'Für das Konzert gibt es zusätzliche Sitzplätze.'],
            ['id' => 'statement_4', 'number' => 4, 'text' => 'Der Programmierkurs startet nächste Woche.'],
            ['id' => 'statement_5', 'number' => 5, 'text' => 'Die Stadt sucht Freiwillige für Samstag.'],
        ],
        'correct' => [
            'statement_1' => 'true',
            'statement_2' => 'false',
            'statement_3' => 'true',
            'statement_4' => 'false',
            'statement_5' => 'true',
        ],
    ];

    if ($withExplanations) {
        $content['explanation'] = [
            'statement_1' => ['correct_answer' => 'true', 'reason' => 'Die Markthalle wird direkt genannt.', 'evidence' => 'in die Markthalle verlegt'],
            'statement_2' => ['correct_answer' => 'false', 'reason' => 'Die Brücke bleibt geschlossen.', 'evidence' => 'bis Montag geschlossen'],
            'statement_3' => ['correct_answer' => 'true', 'reason' => 'Zusätzliche Sitzplätze werden angekündigt.', 'evidence' => 'zusätzliche Sitzplätze'],
            'statement_4' => ['correct_answer' => 'false', 'reason' => 'Der Kurs beginnt schon morgen.', 'evidence' => 'bereits morgen'],
            'statement_5' => ['correct_answer' => 'true', 'reason' => 'Freiwillige werden gesucht.', 'evidence' => 'Freiwillige für eine Pflanzaktion'],
        ];
    }

    return [
        'topic' => 'Stadtmagazin am Morgen',
        'difficulty' => 'medium',
        'content' => $content,
    ];
}

/**
 * @return array<string, mixed>
 */
function makeListeningLongPayload(bool $withExplanations = true, int $transcriptWordMultiplier = 2): array
{
    $baseTranscript = implode(' ', [
        'Moderator: Guten Abend und willkommen zum Rundfunk-Interview.',
        'Heute sprechen wir über neue Arbeitsmodelle in mittelständischen Betrieben.',
        'Gast: Danke für die Einladung. In den letzten zwei Jahren haben wir unser Schichtsystem umgestellt und dadurch deutlich flexiblere Einsatzzeiten geschaffen.',
        'Moderator: Welche konkreten Auswirkungen sehen Sie im Alltag?',
        'Gast: Die Teams planen früher gemeinsam, dadurch gibt es weniger kurzfristige Ausfälle und die Übergaben zwischen den Schichten funktionieren ruhiger.',
        'Moderator: Hat sich das auch auf die Zufriedenheit der Mitarbeitenden ausgewirkt?',
        'Gast: Ja, viele Kolleginnen und Kollegen berichten von weniger Stress, weil private Termine besser planbar sind.',
        'Moderator: Gab es zu Beginn auch Widerstände?',
        'Gast: Natürlich, vor allem wegen der Sorge vor zusätzlicher Bürokratie, aber nach drei Monaten war der Aufwand deutlich geringer als erwartet.',
        'Moderator: Welche Rolle spielt Weiterbildung bei diesem Prozess?',
        'Gast: Eine zentrale Rolle. Alle Teamleitungen haben Schulungen zur Kommunikation und Konfliktlösung erhalten.',
        'Moderator: Und wie bewerten Sie die wirtschaftlichen Effekte?',
        'Gast: Wir konnten die Produktivität moderat steigern, ohne die Arbeitszeit zu verlängern, und gleichzeitig die Krankmeldungen leicht senken.',
        'Moderator: Was ist Ihr wichtigster Rat für andere Betriebe?',
        'Gast: Nicht zu schnell starten, sondern zuerst mit den Mitarbeitenden klare Regeln entwickeln und diese dann konsequent prüfen.',
    ]);
    $transcript = trim(implode(' ', array_fill(0, $transcriptWordMultiplier, $baseTranscript)));

    $content = [
        'format' => 'listening_long_true_false',
        'instructions' => 'Sie hören ein Interview. Entscheiden Sie, ob die Aussagen richtig oder falsch sind.',
        'audio' => [
            'title' => 'Rundfunk-Interview: Arbeitsmodelle im Mittelstand',
            'audio_notes' => 'Rundfunk-Interview mit Moderator und Gast, sachlich und gut verständlich gesprochen.',
        ],
        'transcript' => $transcript,
        'context' => [
            'speaker' => 'Moderator und Betriebsleiter',
            'replay_limit' => 1,
        ],
        'statements' => [
            ['id' => 'statement_1', 'number' => 1, 'text' => 'Das Interview handelt von neuen Arbeitsmodellen in mittelständischen Betrieben.'],
            ['id' => 'statement_2', 'number' => 2, 'text' => 'Der Gast sagt, dass die Umstellung nur wenige Wochen gedauert hat.'],
            ['id' => 'statement_3', 'number' => 3, 'text' => 'Durch gemeinsame Planung gibt es weniger kurzfristige Ausfälle.'],
            ['id' => 'statement_4', 'number' => 4, 'text' => 'Die Mitarbeitenden berichten laut Gast über mehr Stress.'],
            ['id' => 'statement_5', 'number' => 5, 'text' => 'Zu Beginn gab es keine Bedenken im Team.'],
            ['id' => 'statement_6', 'number' => 6, 'text' => 'Der anfängliche Mehraufwand war nach einiger Zeit geringer als erwartet.'],
            ['id' => 'statement_7', 'number' => 7, 'text' => 'Für Teamleitungen wurden Schulungen angeboten.'],
            ['id' => 'statement_8', 'number' => 8, 'text' => 'Die Produktivität sank nach der Umstellung deutlich.'],
            ['id' => 'statement_9', 'number' => 9, 'text' => 'Die Arbeitszeit wurde verlängert, um bessere Ergebnisse zu erzielen.'],
            ['id' => 'statement_10', 'number' => 10, 'text' => 'Der Gast empfiehlt, vor dem Start klare Regeln gemeinsam zu entwickeln.'],
        ],
        'correct' => [
            'statement_1' => 'true',
            'statement_2' => 'false',
            'statement_3' => 'true',
            'statement_4' => 'false',
            'statement_5' => 'false',
            'statement_6' => 'true',
            'statement_7' => 'true',
            'statement_8' => 'false',
            'statement_9' => 'false',
            'statement_10' => 'true',
        ],
    ];

    if ($withExplanations) {
        $content['explanation'] = [
            'statement_1' => ['correct_answer' => 'true', 'reason' => 'Moderator nennt das Thema direkt zu Beginn.', 'evidence' => 'sprechen wir über neue Arbeitsmodelle'],
            'statement_2' => ['correct_answer' => 'false', 'reason' => 'Der Gast nennt einen Zeitraum von zwei Jahren.', 'evidence' => 'In den letzten zwei Jahren'],
            'statement_3' => ['correct_answer' => 'true', 'reason' => 'Gemeinsame Planung reduziert kurzfristige Ausfälle.', 'evidence' => 'weniger kurzfristige Ausfälle'],
            'statement_4' => ['correct_answer' => 'false', 'reason' => 'Es wird von weniger Stress berichtet.', 'evidence' => 'berichten von weniger Stress'],
            'statement_5' => ['correct_answer' => 'false', 'reason' => 'Der Gast erwähnt ausdrücklich anfängliche Widerstände.', 'evidence' => 'Gab es zu Beginn auch Widerstände?'],
            'statement_6' => ['correct_answer' => 'true', 'reason' => 'Nach drei Monaten war der Aufwand geringer.', 'evidence' => 'Aufwand deutlich geringer als erwartet'],
            'statement_7' => ['correct_answer' => 'true', 'reason' => 'Schulungen für Teamleitungen werden klar genannt.', 'evidence' => 'Teamleitungen haben Schulungen'],
            'statement_8' => ['correct_answer' => 'false', 'reason' => 'Der Gast sagt, die Produktivität wurde gesteigert.', 'evidence' => 'Produktivität moderat steigern'],
            'statement_9' => ['correct_answer' => 'false', 'reason' => 'Die Steigerung erfolgte ohne Verlängerung der Arbeitszeit.', 'evidence' => 'ohne die Arbeitszeit zu verlängern'],
            'statement_10' => ['correct_answer' => 'true', 'reason' => 'Der Rat lautet, zunächst Regeln gemeinsam festzulegen.', 'evidence' => 'klare Regeln entwickeln'],
        ];
    }

    return [
        'topic' => 'Rundfunk-Interview zu Arbeitsmodellen',
        'difficulty' => 'medium',
        'content' => $content,
    ];
}

/**
 * @return array<string, mixed>
 */
function makeListeningSegmentedPayload(int $segmentWordMultiplier = 1): array
{
    $intro = 'Guten Morgen. Hier sind die Nachrichten aus den Regionen. Sie hören jetzt fünf Meldungen aus Stadtleben, Verkehr, Gesundheit, Forschung und Kultur.';

    $segmentTexts = [
        'Berlin. In mehreren Bezirken startet heute ein neues Programm zur Begrünung von Schulhöfen. Nach Angaben der Verwaltung sollen zusätzliche Bäume gepflanzt, schattige Sitzbereiche gebaut und versiegelte Flächen teilweise geöffnet werden.',
        'Bonn. Wegen umfangreicher Gleisarbeiten kommt es auf der Strecke zwischen Bonn und Köln noch bis Freitag zu Einschränkungen. Mehrere Regionalzüge fallen am Morgen aus und Pendler müssen mehr Zeit einplanen.',
        'Freiburg. Das Gesundheitsamt startet kommende Woche eine Informationskampagne zum Schutz vor Zecken. In Schulen und Vereinen sollen Eltern und Kinder erfahren, wie man sich nach Ausflügen richtig kontrolliert.',
        'München. Ein Forschungsteam der Universität hat ein Sensorsystem für Fahrradwege vorgestellt. Die Technik soll Schäden auf dem Belag schneller erkennen und automatisch an die zuständigen Stellen melden.',
        'Dresden. Für das Kulturfestival am ersten Maiwochenende sind deutlich mehr Veranstaltungen geplant als im Vorjahr. Für einige Führungen ist jedoch eine vorherige Online-Anmeldung erforderlich.',
    ];

    $segments = [];

    foreach ($segmentTexts as $index => $baseText) {
        $segmentNumber = $index + 1;
        $segmentText = trim(implode(' ', array_fill(0, $segmentWordMultiplier, $baseText)));

        $segments[] = [
            'id' => "segment_{$segmentNumber}",
            'number' => $segmentNumber,
            'voice_profile' => 'news_main',
            'segment_text' => $segmentText,
            'statement_id' => "statement_{$segmentNumber}",
            'statement_text' => "Aussage {$segmentNumber}",
            'correct_answer' => $segmentNumber === 2 ? 'false' : 'true',
            'reason' => "Begruendung {$segmentNumber}",
            'evidence' => "Hinweis {$segmentNumber}",
        ];
    }

    return [
        'topic' => 'Regionale Nachrichten',
        'difficulty' => 'medium',
        'content' => [
            'format' => 'listening_segmented_true_false',
            'instructions' => 'Sie hören nun eine Nachrichtensendung. Dazu sollen Sie fünf Aufgaben lösen. Sie hören die Nachrichtensendung nur einmal.',
            'audio' => [
                'title' => 'Regionale Nachrichten',
                'audio_notes' => 'Nachrichtensendung mit neutraler Sprecherstimme und fünf klar getrennten, ausführlicheren Meldungen aus den Regionen.',
            ],
            'intro' => [
                'text' => $intro,
                'voice_profile' => 'anchor_main',
            ],
            'segments' => $segments,
        ],
    ];
}

it('regenerates explanations when the first draft explanations are weak', function () {
    $generator = new FakeGeminiQuestionGenerator(
        responses: [makePerGapPayload(withStructuredExplanations: false)],
        generatedExplanations: makePerGapPayload()['content']['explanation'],
    );

    $result = $generator->generateQuestion([
        'format' => 'per_gap',
        'difficulty' => 'medium',
        'topic_hint' => 'Beschwerde',
    ]);

    expect($generator->explanationCalls)->toBe(1)
        ->and($generator->preparedInteractiveBudget)->toBeTrue()
        ->and($result['content']['explanation']['gap_1']['rule_type'])->toBe('Konjunktion')
        ->and($result['content']['explanation']['gap_1']['contrast'])->not->toBe('')
        ->and($result['quality_report']['passed'])->toBeTrue();
});

it('regenerates missing explanations for listening short drafts through a dedicated shape', function () {
    $generator = new FakeGeminiQuestionGenerator(
        responses: [makeListeningShortPayload(withExplanations: false)],
    );

    app()->instance(ListeningDraftExplanationGenerationService::class, new class extends ListeningDraftExplanationGenerationService
    {
        /**
         * @param  array<string, mixed>  $content
         * @return array<string, array<string, string>>
         */
        public function generate(array $content, string $topic = '', string $qualityRetryHint = ''): array
        {
            return makeListeningShortPayload()['content']['explanation'];
        }
    });

    $result = $generator->generateQuestion([
        'format' => 'listening_short_true_false',
        'difficulty' => 'medium',
        'topic_hint' => 'Stadtmeldungen',
    ]);

    expect($result['content']['explanation']['statement_1']['correct_answer'])->toBe('true')
        ->and($result['content']['explanation']['statement_1']['reason'])->toBe('Die Markthalle wird direkt genannt.')
        ->and($result['quality_report']['passed'])->toBeTrue();
});

it('regenerates missing explanations for listening long drafts through a dedicated shape', function () {
    $generator = new FakeGeminiQuestionGenerator(
        responses: [makeListeningLongPayload(withExplanations: false, transcriptWordMultiplier: 2)],
    );

    app()->instance(ListeningDraftExplanationGenerationService::class, new class extends ListeningDraftExplanationGenerationService
    {
        /**
         * @param  array<string, mixed>  $content
         * @return array<string, array<string, string>>
         */
        public function generate(array $content, string $topic = '', string $qualityRetryHint = ''): array
        {
            return makeListeningLongPayload(transcriptWordMultiplier: 2)['content']['explanation'];
        }
    });

    $result = $generator->generateQuestion([
        'format' => 'listening_long_true_false',
        'difficulty' => 'medium',
        'topic_hint' => 'Arbeitswelt',
        'module_slug' => 'hoeren-teil-2',
    ]);

    expect($result['content']['explanation']['statement_10']['correct_answer'])->toBe('true')
        ->and($result['content']['explanation']['statement_10']['reason'])->toBe('Der Rat lautet, zunächst Regeln gemeinsam festzulegen.')
        ->and($result['quality_report']['passed'])->toBeTrue();
});

it('retries segmented listening drafts when the transcript is too short for teil 1 runtime', function () {
    $generator = new FakeGeminiQuestionGenerator([
        makeListeningSegmentedPayload(segmentWordMultiplier: 1),
        makeListeningSegmentedPayload(segmentWordMultiplier: 2),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'listening_segmented_true_false',
        'difficulty' => 'medium',
        'topic_hint' => 'Regionale Nachrichten',
    ]);

    expect(count($generator->prompts))->toBe(2)
        ->and($result['quality_report']['passed'])->toBeTrue()
        ->and(GeminiService::countTextWords((string) ($result['content']['transcript'] ?? '')))->toBeGreaterThanOrEqual(280);
});

it('retries long listening drafts when the transcript is too short for teil 2 runtime', function () {
    $generator = new FakeGeminiQuestionGenerator([
        makeListeningLongPayload(transcriptWordMultiplier: 1),
        makeListeningLongPayload(transcriptWordMultiplier: 2),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'listening_long_true_false',
        'difficulty' => 'medium',
        'topic_hint' => 'Arbeitswelt',
        'module_slug' => 'hoeren-teil-2',
    ]);

    expect(count($generator->prompts))->toBe(2)
        ->and($generator->prompts[0])->toContain('Hören Teil 2')
        ->and($generator->prompts[0])->toContain('Rundfunk-Interview')
        ->and($generator->prompts[1])->toContain('Teil 2 Interview-Transcript hatte')
        ->and($result['quality_report']['passed'])->toBeTrue()
        ->and(GeminiService::countTextWords((string) ($result['content']['transcript'] ?? '')))->toBeGreaterThanOrEqual(320);
});

it('retries hoeren teil 1 drafts when content slips into teil 3 framing', function () {
    $brokenPayload = makeListeningSegmentedPayload(segmentWordMultiplier: 2);
    $brokenPayload['content']['instructions'] = 'Sie hören jetzt fünf kurze Texte. Dazu sollen Sie fünf Aufgaben lösen. Sie hören diese Ansagen nur einmal.';
    $brokenPayload['content']['audio']['title'] = 'Fünf kurze Ansagen';
    $brokenPayload['content']['audio']['audio_notes'] = 'Kurznachrichten und kurze Ansagen.';
    $brokenPayload['content']['intro']['text'] = 'Sie hören jetzt fünf kurze Texte aus dem Stadtfunk.';
    $brokenPayload['content']['segments'][0]['voice_profile'] = 'news_a';
    $brokenPayload['content']['segments'][0]['statement_id'] = 'statement_2';

    $generator = new FakeGeminiQuestionGenerator([
        $brokenPayload,
        makeListeningSegmentedPayload(segmentWordMultiplier: 2),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'listening_segmented_true_false',
        'difficulty' => 'medium',
        'topic_hint' => 'Regionale Nachrichten',
        'module_slug' => 'hoeren-teil-1',
    ]);

    expect(count($generator->prompts))->toBe(2)
        ->and($generator->prompts[1])->toContain('Fuer hoeren-teil-1 gilt strikt')
        ->and($result['quality_report']['passed'])->toBeTrue();
});

it('retries the full generation when the structural output does not meet the official format', function () {
    $generator = new FakeGeminiQuestionGenerator([
        makeSharedPoolPayload(poolCount: 13, wordMultiplier: 10),
        makeSharedPoolPayload(),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'shared_pool',
        'difficulty' => 'medium',
        'topic_hint' => 'Stadtleben',
    ]);

    expect(count($generator->prompts))->toBe(2)
        ->and($generator->prompts[1])->toContain('hat die Modulregeln nicht sauber eingehalten')
        ->and($result['quality_report']['passed'])->toBeTrue();
});

it('adds targeted retry guidance for short shared-pool texts and missing pool answers', function () {
    $brokenPayload = makeSharedPoolPayload(wordMultiplier: 1);
    $brokenPayload['content']['options_pool'][0] = 'ablenkung';

    $generator = new FakeGeminiQuestionGenerator([
        $brokenPayload,
        makeSharedPoolPayload(),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'shared_pool',
        'difficulty' => 'medium',
        'topic_hint' => 'Weiterbildung',
    ]);

    expect(count($generator->prompts))->toBe(2)
        ->and($generator->prompts[1])->toContain('Jede richtige Antwort aus "correct" MUSS woertlich und exakt einmal im "options_pool" stehen')
        ->and($generator->prompts[1])->toContain('Schreibe mindestens 260 Woerter netto')
        ->and($result['quality_report']['passed'])->toBeTrue();
});

it('adds targeted retry guidance for shared-pool relative pronoun mismatches', function () {
    $generator = new FakeGeminiQuestionGenerator([
        makePayloadWithHiddenAntecedentRelativePronoun(),
        makeSharedPoolPayload(),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'shared_pool',
        'difficulty' => 'medium',
        'topic_hint' => 'Weiterbildung',
    ]);

    expect(count($generator->prompts))->toBe(2)
        ->and($generator->prompts[1])->toContain('Verwende Relativpronomen nur, wenn direkt links im sichtbaren Satz ein eindeutiges Bezugswort steht')
        ->and($result['quality_report']['passed'])->toBeTrue();
});

it('uses extended default Gemini timeouts for interactive generation', function () {
    expect((int) config('services.gemini.connect_timeout_seconds'))->toBe(10)
        ->and((int) config('services.gemini.request_timeout_seconds'))->toBe(60)
        ->and((int) config('services.gemini.explanation_timeout_seconds'))->toBe(45)
        ->and((int) config('services.gemini.request_budget_seconds'))->toBe(120);
});

it('wraps Gemini connection timeouts in a readable runtime exception', function () {
    config()->set('services.gemini.key', 'test-key');
    config()->set('services.gemini.model', 'gemini-test-model');

    Http::fake([
        '*' => Http::failedConnection(),
    ]);

    $service = new GeminiHttpClientProbe;

    expect(fn () => $service->invokeCallGeminiJson('prompt'))
        ->toThrow(RuntimeException::class, 'Gemini request timed out or could not connect');
});

it('retries per-gap generation when the text does not look like a complete email', function () {
    $generator = new FakeGeminiQuestionGenerator([
        makeBrokenPerGapPayload(),
        makePerGapPayload(),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'per_gap',
        'difficulty' => 'medium',
        'topic_hint' => 'Beschwerde',
    ]);

    expect(count($generator->prompts))->toBe(1)
        ->and($result['quality_report']['passed'])->toBeTrue()
        ->and($result['quality_report']['warnings'])->toContain(
            'Per-gap text does not clearly show a conventional greeting and closing formula.'
        );
});

it('returns warnings instead of failing hard when explanations stay weak', function () {
    $generator = new FakeGeminiQuestionGenerator([
        makePerGapPayload(withStructuredExplanations: false),
        makePerGapPayload(withStructuredExplanations: false),
        makePerGapPayload(withStructuredExplanations: false),
    ], generatedExplanations: []);

    $result = $generator->generateQuestion([
        'format' => 'per_gap',
        'difficulty' => 'medium',
        'topic_hint' => 'Arbeit',
    ]);

    expect($result['quality_report']['passed'])->toBeTrue()
        ->and($result['quality_report']['warnings'])->toContain('Explanations need editorial review before publishing this question.')
        ->and($result['quality_report']['explanations_status'])->toBe('needs_review');
});

it('stops retrying once the interactive time budget is exhausted', function () {
    config()->set('services.gemini.max_generation_attempts', 3);

    $generator = new FakeGeminiQuestionGenerator([
        makeSharedPoolPayload(poolCount: 13, wordMultiplier: 10),
        makeSharedPoolPayload(poolCount: 13, wordMultiplier: 10),
        makeSharedPoolPayload(),
    ]);
    $generator->timeline = [0.0, 0.0, 26.0, 52.0];

    expect(fn () => $generator->generateQuestion([
        'format' => 'shared_pool',
        'difficulty' => 'medium',
        'topic_hint' => 'Stadtleben',
    ]))->toThrow(RuntimeException::class, 'interactive time budget');

    expect(count($generator->prompts))->toBe(2);
});

it('regenerates explanations when rule types, contrasts, and examples are not specific enough', function () {
    $regeneratedExplanations = makeSharedPoolPayload()['content']['explanation'];
    $regeneratedExplanations['gap_1'] = [
        'answer' => 'ob',
        'rule_type' => 'Konjunktion',
        'reason' => 'Hier passt ob, weil nach der Formulierung eine indirekte Entscheidungsfrage eingeleitet wird.',
        'pattern' => 'die Frage, ob ...',
        'contrast' => 'Der Ausdruck ausdruck11 passt hier nicht, weil er keine indirekte Frage einleitet.',
        'example' => 'Ich frage mich, ob er morgen kommt.',
    ];

    $generator = new FakeGeminiQuestionGenerator(
        responses: [makeSharedPoolPayloadWithWeakExplanationChecks()],
        generatedExplanations: $regeneratedExplanations,
    );

    $result = $generator->generateQuestion([
        'format' => 'shared_pool',
        'difficulty' => 'medium',
        'topic_hint' => 'Klimaschutz',
    ]);

    expect($generator->explanationCalls)->toBe(1)
        ->and($result['quality_report']['passed'])->toBeTrue();
});

it('keeps weak pattern and rule_type mismatch as review warnings instead of hard-failing the question', function () {
    $payload = makePerGapPayload();
    $payload['content']['correct']['gap_2'] = 'dass';
    $payload['content']['options']['gap_2'] = ['dass', 'ob', 'weil'];
    $payload['content']['explanation']['gap_2'] = [
        'answer' => 'dass',
        'rule_type' => 'Verb mit Präposition',
        'reason' => 'Hier passt dass, weil der Nebensatz den Inhalt der Aussage genauer ausführt.',
        'pattern' => '',
        'contrast' => 'Ob passt hier nicht, weil keine indirekte Frage eingeleitet wird.',
        'example' => 'Ich glaube, dass er morgen kommt.',
    ];

    $generator = new FakeGeminiQuestionGenerator(
        responses: [$payload],
        generatedExplanationResponses: [$payload['content']['explanation']],
    );

    $result = $generator->generateQuestion([
        'format' => 'per_gap',
        'difficulty' => 'medium',
        'topic_hint' => 'Wohnen',
    ]);

    expect($result['quality_report']['passed'])->toBeTrue()
        ->and($result['quality_report']['warnings'])->toContain(
            'Explanation for gap_2 uses a rule_type that does not fit the actual answer.'
        )
        ->and($result['quality_report']['warnings'])->toContain(
            'Explanation for gap_2 needs a concrete pattern or construction.'
        )
        ->and($result['quality_report']['review_gap_ids'])->toContain('gap_2')
        ->and($result['quality_report']['explanations_status'])->toBe('needs_review');
});

it('normalizes shared-pool answers and retries explanations before failing the whole question', function () {
    $payload = makeSharedPoolPayload();
    $payload['content']['correct']['gap_5'] = 'als';
    $payload['content']['options_pool'][4] = 'als ';
    $payload['content']['explanation']['gap_5'] = [
        'answer' => 'vergleich',
        'rule_type' => 'Grammatik',
        'reason' => 'Hier passt als, weil ein Vergleich markiert wird.',
        'pattern' => '',
        'contrast' => 'Ein anderer Ausdruck passt hier nicht.',
        'example' => 'Sie ist groesser als ich.',
    ];

    $fixedExplanations = $payload['content']['explanation'];
    $fixedExplanations['gap_5'] = [
        'answer' => 'als',
        'rule_type' => 'Vergleichspartikel / Konnektor',
        'reason' => 'Hier passt als, weil nach dem Komparativ die Vergleichsstruktur vervollstaendigt wird.',
        'pattern' => 'groesser als',
        'contrast' => 'Der Ausdruck ausdruck11 passt hier nicht, weil er keinen Vergleich nach dem Komparativ markiert.',
        'example' => 'Meine Schwester ist juenger als ich.',
    ];

    $generator = new FakeGeminiQuestionGenerator(
        responses: [$payload],
        generatedExplanationResponses: [
            $payload['content']['explanation'],
            $fixedExplanations,
        ],
    );

    $result = $generator->generateQuestion([
        'format' => 'shared_pool',
        'difficulty' => 'medium',
        'topic_hint' => 'Vergleiche',
    ]);

    expect($generator->explanationCalls)->toBe(1)
        ->and($result['quality_report']['passed'])->toBeTrue()
        ->and($result['content']['correct']['gap_5'])->toBe('als')
        ->and($result['content']['options_pool'])->toContain('als');
});

it('accepts shared-pool answers when the pool only differs by case and trims broader rule type variants', function () {
    $payload = makeSharedPoolPayload();
    $payload['content']['correct']['gap_1'] = 'Da';
    $payload['content']['correct']['gap_2'] = 'Denn';
    $payload['content']['correct']['gap_3'] = 'Wenn';
    $payload['content']['correct']['gap_4'] = 'die';
    $payload['content']['options_pool'][0] = 'da';
    $payload['content']['options_pool'][1] = 'denn ';
    $payload['content']['options_pool'][2] = 'wenn';
    $payload['content']['options_pool'][3] = 'Die';
    $payload['content']['explanation']['gap_2']['rule_type'] = 'Interrogativpronomen';
    $payload['content']['explanation']['gap_7']['rule_type'] = 'Vergleichskonjunktion';

    $validator = app(QuestionGenerationQualityValidator::class);
    $report = $validator->validateGeneratedQuestion(
        $validator->normalizeGeneratedQuestion($payload),
        'shared_pool',
    );

    expect($report['errors'])->not->toContain("Shared-pool options are missing the correct answer 'Da'.")
        ->and($report['errors'])->not->toContain("Shared-pool options are missing the correct answer 'Denn'.")
        ->and($report['errors'])->not->toContain("Shared-pool options are missing the correct answer 'Wenn'.")
        ->and($report['errors'])->not->toContain("Shared-pool options are missing the correct answer 'die'.")
        ->and($report['errors'])->not->toContain('Explanation for gap_2 must use an allowed rule_type.')
        ->and($report['errors'])->not->toContain('Explanation for gap_7 must use an allowed rule_type.');
});

it('returns the question with warnings when explanations are still weak after one retry', function () {
    $payload = makePerGapPayload(withStructuredExplanations: false);

    $generator = new FakeGeminiQuestionGenerator(
        responses: [$payload],
        generatedExplanationResponses: [[]],
    );

    $result = $generator->generateQuestion([
        'format' => 'per_gap',
        'difficulty' => 'medium',
        'topic_hint' => 'Beschwerde',
    ]);

    expect($generator->explanationCalls)->toBe(1)
        ->and($result['quality_report']['passed'])->toBeTrue()
        ->and($result['quality_report']['warnings'])->toContain('Explanations need editorial review before publishing this question.');
});

it('does not soften explanation review when structural shared-pool errors are still present', function () {
    $payload = makeSharedPoolPayload(poolCount: 15);
    $payload['content']['options_pool'][0] = 'doch';
    $payload['content']['explanation']['gap_1'] = [
        'answer' => 'falsch',
        'rule_type' => 'Interrogativadverb',
        'reason' => 'Zu schwache Erklaerung fuer den Testfall.',
        'pattern' => '',
        'contrast' => 'Ein anderer Ausdruck passt hier nicht.',
        'example' => 'Dies ist kein passendes Beispiel.',
    ];

    $generator = new FakeGeminiQuestionGenerator(
        responses: [$payload, $payload, $payload],
        generatedExplanationResponses: [[], [], []],
    );

    expect(fn () => $generator->generateQuestion([
        'format' => 'shared_pool',
        'difficulty' => 'medium',
        'topic_hint' => 'Arbeit',
    ]))->toThrow(RuntimeException::class, "Shared-pool options are missing the correct answer 'ausdruck1'.");
});

it('does not return an invalid shared-pool question when time budget is too low for explanation retry', function () {
    $payload = makeSharedPoolPayload();
    $payload['content']['options_pool'][0] = 'doch';
    $payload['content']['explanation']['gap_1'] = [
        'answer' => 'falsch',
        'rule_type' => 'Interrogativadverb',
        'reason' => 'Zu schwache Erklaerung fuer den Testfall.',
        'pattern' => '',
        'contrast' => 'Ein anderer Ausdruck passt hier nicht.',
        'example' => 'Dies ist kein passendes Beispiel.',
    ];

    $generator = new FakeGeminiQuestionGenerator(responses: [$payload, $payload, $payload]);
    $generator->timeline = [0.0, 40.0, 49.5, 49.8];

    expect(fn () => $generator->generateQuestion([
        'format' => 'shared_pool',
        'difficulty' => 'medium',
        'topic_hint' => 'Arbeit',
    ]))->toThrow(RuntimeException::class, "Shared-pool options are missing the correct answer 'ausdruck1'.");
});

it('accepts email-style teil 1 texts without failing on one exact closing formula', function () {
    $generator = new FakeGeminiQuestionGenerator([
        makePerGapPayloadWithoutClassicGreeting(),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'per_gap',
        'difficulty' => 'medium',
        'topic_hint' => 'Beschwerde',
    ]);

    expect($result['quality_report']['passed'])->toBeTrue();
});

it('accepts examples that demonstrate the same construction via the pattern', function () {
    $payload = makePerGapPayload();
    $payload['content']['correct']['gap_3'] = 'warten';
    $payload['content']['options']['gap_3'] = ['warten', 'denken', 'sehen'];
    $payload['content']['explanation']['gap_3'] = [
        'answer' => 'warten',
        'rule_type' => 'Verb mit Präposition',
        'reason' => 'Hier passt warten, weil das Verb in dieser Aussage mit einer festen Präposition verwendet wird.',
        'pattern' => 'warten auf + Akk.',
        'contrast' => 'Der Distraktor denken passt hier nicht, weil er eine andere Rektion verlangt.',
        'example' => 'Wir warten auf den Bus vor dem Bahnhof.',
    ];

    $generator = new FakeGeminiQuestionGenerator([
        $payload,
    ]);

    $result = $generator->generateQuestion([
        'format' => 'per_gap',
        'difficulty' => 'medium',
        'topic_hint' => 'Arbeit',
    ]);

    expect($result['quality_report']['passed'])->toBeTrue();
});

it('retries the full generation when a relative-pronoun explanation invents a hidden antecedent', function () {
    $generator = new FakeGeminiQuestionGenerator([
        makePayloadWithHiddenAntecedentRelativePronoun(),
        makeSharedPoolPayload(),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'shared_pool',
        'difficulty' => 'medium',
        'topic_hint' => 'Weiterbildung',
    ]);

    expect(count($generator->prompts))->toBe(2)
        ->and($result['quality_report']['passed'])->toBeTrue();
});

it('retries the full generation when a relative clause around the gap is locally implausible', function () {
    $generator = new FakeGeminiQuestionGenerator([
        makePayloadWithBrokenPassiveRelativeClause(),
        makePerGapPayload(),
    ]);

    $result = $generator->generateQuestion([
        'format' => 'per_gap',
        'difficulty' => 'medium',
        'topic_hint' => 'Beschwerde',
    ]);

    expect(count($generator->prompts))->toBe(2)
        ->and($result['quality_report']['passed'])->toBeTrue();
});
