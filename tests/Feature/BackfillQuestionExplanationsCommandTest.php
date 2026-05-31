<?php

use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validBackfillContent(mixed $explanation): array
{
    $options = [];
    $correct = [];
    $textParts = ['Hallo Frau Becker,'];

    for ($i = 1; $i <= 10; $i++) {
        $gapId = "gap_{$i}";
        $correctAnswer = "wort{$i}";
        $options[$gapId] = [$correctAnswer, "falsch{$i}a", "falsch{$i}b"];
        $correct[$gapId] = $correctAnswer;
        $textParts[] = "Im Absatz {$i} erklaere ich die Situation genauer und setze {{".$gapId.'}} in einen realistischen B2-Alltagskontext.';
    }

    $normalizedExplanation = is_array($explanation) ? $explanation : [];

    foreach (array_keys($correct) as $gapId) {
        $normalizedExplanation[$gapId] ??= "Alte Erklärung fuer {$gapId}.";
    }

    $filler = str_repeat('Dieser Text liefert ausreichend thematischen Inhalt fuer eine hochwertige telc B2 Allgemein Aufgabe. ', 8);
    $paragraphs = [
        $textParts[0],
        $filler.' '.$textParts[1].' '.$textParts[2].' '.$textParts[3],
        $textParts[4].' '.$textParts[5].' '.$textParts[6],
        $textParts[7].' '.$textParts[8].' '.$textParts[9].' '.$textParts[10],
        'Vielen Dank fuer Ihre Rueckmeldung.'."\n".'Viele Gruesse'."\n".'Anna Meier',
    ];

    return [
        'text' => implode("\n\n", $paragraphs),
        'options' => $options,
        'correct' => $correct,
        'explanation' => $normalizedExplanation,
    ];
}

it('backfills structured explanations without changing question text', function () {
    $exam = Exam::factory()->create(['is_active' => true]);
    $module = Module::factory()->create(['exam_id' => $exam->id]);
    $question = Question::factory()->create([
        'module_id' => $module->id,
        'content' => validBackfillContent([
            'gap_1' => 'Alte Erklärung.',
        ]),
    ]);

    $fakeGemini = new class extends GeminiService
    {
        public function __construct() {}

        public function generateExplanations(array $content, string $topic = '', string $qualityRetryHint = ''): array
        {
            return collect($content['correct'])
                ->mapWithKeys(function (string $answer, string $gapId) use ($content): array {
                    $alternative = collect($content['options'][$gapId] ?? [])
                        ->first(fn (string $option): bool => $option !== $answer) ?? 'ob';

                    return [
                        $gapId => [
                            'answer' => $answer,
                            'rule_type' => 'Konjunktion',
                            'reason' => "Im sichtbaren Satz steht ein klares grammatisches Signal, deshalb passt hier {$answer}.",
                            'pattern' => '',
                            'contrast' => "Die typische Falle ist {$alternative}: {$alternative} passt hier nicht, weil der Satz eine andere Struktur verlangt.",
                            'example' => "Ein kurzer Beispielsatz zeigt, wie {$answer} in derselben Struktur funktioniert.",
                        ],
                    ];
                })
                ->all();
        }
    };

    app()->instance(GeminiService::class, $fakeGemini);

    $this->artisan('questions:backfill-explanations', [
        '--question_id' => [$question->id],
    ])->assertSuccessful();

    $question->refresh();

    expect($question->content['text'])->toContain('{{gap_1}}')
        ->and($question->content['correct']['gap_1'])->toBe('wort1')
        ->and($question->content['explanation']['gap_1']['rule_type'])->toBe('Konjunktion')
        ->and($question->content['explanation']['gap_1']['answer'])->toBe('wort1');
});

it('skips questions that already have structured explanations unless forced', function () {
    $exam = Exam::factory()->create(['is_active' => true]);
    $module = Module::factory()->create(['exam_id' => $exam->id]);
    $structuredExplanations = collect(range(1, 10))
        ->mapWithKeys(fn (int $index): array => [
            "gap_{$index}" => [
                'answer' => "wort{$index}",
                'rule_type' => 'Konjunktion',
                'reason' => "Vor dem lokalen Signal steht eine klare inhaltliche Verbindung, deshalb passt wort{$index}.",
                'pattern' => '',
                'contrast' => "Die typische Falle ist falsch{$index}a, aber dieser Distraktor wuerde hier eine andere Funktion ausloesen.",
                'example' => "Ich nutze wort{$index} bewusst in einem neuen Beispielsatz.",
            ],
        ])
        ->all();

    $question = Question::factory()->create([
        'module_id' => $module->id,
        'content' => validBackfillContent($structuredExplanations),
    ]);

    $fakeGemini = new class extends GeminiService
    {
        public int $calls = 0;

        public function __construct() {}

        public function generateExplanations(array $content, string $topic = '', string $qualityRetryHint = ''): array
        {
            $this->calls++;

            return [
                'gap_1' => [
                    'answer' => 'dass',
                    'rule_type' => 'Neu',
                    'reason' => 'Neu generiert.',
                    'pattern' => '',
                    'contrast' => '',
                    'example' => '',
                ],
            ];
        }
    };

    app()->instance(GeminiService::class, $fakeGemini);

    $this->artisan('questions:backfill-explanations', [
        '--question_id' => [$question->id],
    ])->assertSuccessful();

    $question->refresh();

    expect($fakeGemini->calls)->toBe(0)
        ->and($question->content['explanation']['gap_1']['reason'])->toBe('Vor dem lokalen Signal steht eine klare inhaltliche Verbindung, deshalb passt wort1.');
});

it('does not overwrite explanations when generated explanations still need editorial review', function () {
    $exam = Exam::factory()->create(['is_active' => true]);
    $module = Module::factory()->create([
        'exam_id' => $exam->id,
        'slug' => 'sprachbausteine-teil-1',
    ]);
    $question = Question::factory()->create([
        'module_id' => $module->id,
        'content' => validBackfillContent([
            'gap_1' => 'Alte Erklärung.',
        ]),
    ]);

    $fakeGemini = new class extends GeminiService
    {
        public function __construct() {}

        public function generateExplanations(array $content, string $topic = '', string $qualityRetryHint = ''): array
        {
            return [
                'gap_1' => [
                    'answer' => 'dass',
                    'rule_type' => 'Konjunktion',
                    'reason' => 'Das passt hier.',
                    'pattern' => '',
                    'contrast' => '',
                    'example' => '',
                ],
            ];
        }
    };

    app()->instance(GeminiService::class, $fakeGemini);

    $this->artisan('questions:backfill-explanations', [
        '--question_id' => [$question->id],
        '--force' => true,
    ])
        ->expectsOutputToContain('Needs review: 1')
        ->assertSuccessful();

    $question->refresh();

    expect($question->content['explanation']['gap_1'])->toBe('Alte Erklärung.');
});
