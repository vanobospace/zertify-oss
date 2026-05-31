<?php

use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use Database\Seeders\ZertifySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds structured explanations for seeded sprachbausteine questions', function () {
    $this->seed(ZertifySeeder::class);

    $question = Question::query()
        ->where('topic', 'Beschwerde: Mangelhafter Online-Sprachkurs')
        ->firstOrFail();

    expect($question->content['explanation']['gap_1'])->toBeArray()
        ->and($question->content['explanation']['gap_1']['answer'])->toBe('gebucht')
        ->and($question->content['explanation']['gap_1']['rule_type'])->not->toBe('')
        ->and($question->content['explanation']['gap_1']['reason'])->not->toBe('');
});

it('seeds lesen and hoeren modules with one reference task per part', function () {
    $this->seed(ZertifySeeder::class);

    $readingModules = [
        'lesen-teil-1' => 'reading_matching_headlines',
        'lesen-teil-2' => 'reading_article_mc',
        'lesen-teil-3' => 'reading_situations_matching',
    ];

    foreach ($readingModules as $slug => $format) {
        $module = Module::query()->where('slug', $slug)->firstOrFail();
        $questions = $module->questions()->orderBy('order')->get();

        expect($questions)->toHaveCount(1)
            ->and($questions->pluck('format')->unique()->values()->all())->toBe([$format])
            ->and($questions->pluck('status')->unique()->values()->all())->toBe(['published'])
            ->and($questions->pluck('is_active')->unique()->values()->all())->toBe([true]);
    }

    $listeningModules = [
        'hoeren-teil-1' => 'listening_segmented_true_false',
        'hoeren-teil-2' => 'listening_long_true_false',
        'hoeren-teil-3' => 'listening_short_true_false',
    ];

    foreach ($listeningModules as $slug => $format) {
        $module = Module::query()->where('slug', $slug)->firstOrFail();
        $questions = $module->questions()->orderBy('order')->get();

        expect($questions)->toHaveCount(1)
            ->and($questions->pluck('format')->unique()->values()->all())->toBe([$format])
            ->and($questions->pluck('status')->unique()->values()->all())->toBe(['draft'])
            ->and($questions->pluck('is_active')->unique()->values()->all())->toBe([false]);
    }
});

it('seeds comprehension explanations and source metadata for lesen and hoeren', function () {
    $this->seed(ZertifySeeder::class);

    $lesenQuestion = Question::query()
        ->where('topic', 'Lesen 2: Familienleben zwischen Nähe und Konflikt')
        ->firstOrFail();

    $hoerenQuestion = Question::query()
        ->where('topic', 'Hören 2: Digitalisierung im Handwerk')
        ->firstOrFail();

    expect($lesenQuestion->content['explanation']['question_1']['correct_answer'])->toBe('q1_b')
        ->and($lesenQuestion->source_label)->toBe('Internal Zertify seed set')
        ->and($lesenQuestion->content['source']['url'])->toBe('')
        ->and($hoerenQuestion->content['audio']['title'])->not->toBe('')
        ->and($hoerenQuestion->content['explanation']['statement_1']['reason'])->not->toBe('')
        ->and($hoerenQuestion->status)->toBe('draft')
        ->and($hoerenQuestion->audio_source_type)->toBeNull()
        ->and($hoerenQuestion->question_audio_asset_id)->toBeNull();
});

it('seeds reading tasks with expected b2 allgemein content structure', function () {
    $this->seed(ZertifySeeder::class);

    $lesenTeil1 = Question::query()
        ->where('topic', 'Lesen 1: Kultur, Freizeit und Medienpraxis')
        ->firstOrFail();
    $lesenTeil2 = Question::query()
        ->where('topic', 'Lesen 2: Familienleben zwischen Nähe und Konflikt')
        ->firstOrFail();
    $lesenTeil3 = Question::query()
        ->where('topic', 'Lesen 3: Mobilität, Stadtangebote und Zugang')
        ->firstOrFail();

    expect($lesenTeil1->content['headings'])->toHaveCount(10)
        ->and($lesenTeil1->content['texts'])->toHaveCount(5)
        ->and($lesenTeil1->content['correct'])->toHaveCount(5)
        ->and(array_unique(array_values($lesenTeil1->content['correct'])))->toHaveCount(5)
        ->and($lesenTeil1->content['source']['label'])->toBe('Internal Zertify seed set');

    expect($lesenTeil2->content['questions'])->toHaveCount(5)
        ->and(collect($lesenTeil2->content['questions'])->every(fn (array $question): bool => count($question['options']) === 3))->toBeTrue()
        ->and($lesenTeil2->content['correct'])->toHaveCount(5)
        ->and($lesenTeil2->content['explanation']['question_1']['reason'])->not->toBe('');

    expect($lesenTeil3->content['situations'])->toHaveCount(10)
        ->and($lesenTeil3->content['texts'])->toHaveCount(12)
        ->and($lesenTeil3->content['extra_answer']['label'])->toBe('X')
        ->and($lesenTeil3->content['correct'])->toHaveCount(10)
        ->and($lesenTeil3->content['explanation']['situation_1']['correct_answer'])->not->toBe('');
});

it('seeds listening tasks as drafts without audio assets in the seed set', function () {
    $this->seed(ZertifySeeder::class);

    $expectedTopics = [
        'Hören 1: Nachrichten am Mittag',
        'Hören 2: Digitalisierung im Handwerk',
        'Hören 3: Servicehinweise und Ankündigungen',
    ];

    $questions = Question::query()
        ->with('audioAsset')
        ->whereIn('topic', $expectedTopics)
        ->orderBy('topic')
        ->get();

    expect($questions)->toHaveCount(3)
        ->and(QuestionAudioAsset::query()->count())->toBe(0);

    foreach ($questions as $question) {
        expect($question->audioAsset)->toBeNull()
            ->and($question->status)->toBe('draft')
            ->and($question->is_active)->toBe(false)
            ->and($question->content['audio']['title'])->not->toBe('');
    }
});

it('syncs listening seed questions idempotently through the artisan command', function () {
    $this->seed(ZertifySeeder::class);

    $question = Question::query()
        ->where('seed_key', 'hoeren-teil-1.reference.1')
        ->firstOrFail();

    $originalId = $question->id;
    $question->forceFill([
        'topic' => 'Outdated local copy',
        'content' => array_replace($question->content, [
            'transcript' => 'Alter und falscher Transcript.',
        ]),
    ])->save();

    $this->artisan('questions:sync-listening-seeds')
        ->assertSuccessful();

    $question->refresh();

    expect($question->id)->toBe($originalId)
        ->and($question->topic)->toBe('Hören 1: Nachrichten am Mittag')
        ->and(Question::query()->where('seed_key', 'hoeren-teil-1.reference.1')->count())->toBe(1);
});
