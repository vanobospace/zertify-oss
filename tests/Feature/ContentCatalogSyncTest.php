<?php

use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Models\QuestionGenerationTheme;
use App\Models\User;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    config()->set('content_catalog.path', storage_path('framework/testing/content-catalog.json'));

    File::ensureDirectoryExists(dirname((string) config('content_catalog.path')));
    File::delete((string) config('content_catalog.path'));
});

afterEach(function (): void {
    File::delete((string) config('content_catalog.path'));
});

it('exports a canonical content catalog and assigns content keys to manual content', function () {
    $exam = Exam::factory()->create([
        'slug' => 'telc-b2',
        'name' => 'B2 Allgemein',
        'level' => 'B2',
    ]);

    $module = Module::factory()->for($exam)->create([
        'slug' => 'hoeren-teil-1',
        'name' => 'Hören Teil 1',
        'type' => 'listening',
        'default_points' => 1.5,
    ]);

    $manualQuestion = Question::factory()->for($module)->create([
        'seed_key' => null,
        'content_key' => null,
        'topic' => 'Regionalnachrichten am Mittag',
        'format' => 'listening_segmented_true_false',
        'content' => [
            'format' => 'listening_segmented_true_false',
            'intro' => ['text' => 'Guten Tag.'],
            'segments' => [],
            'transcript' => 'Guten Tag. Sie hören die Regionalnachrichten.',
        ],
        'audio_source_type' => Question::AUDIO_SOURCE_ASSET,
        'question_audio_asset_id' => null,
    ]);

    $audioAsset = QuestionAudioAsset::query()->create([
        'label' => 'manual-audio',
        'path' => 'question-audio/generated/manual-audio.wav',
        'disk' => 'public',
        'original_name' => 'manual-audio.wav',
        'transcript_hash' => hash('sha256', 'Guten Tag. Sie hören die Regionalnachrichten.'),
        'generation_metadata' => ['provider' => 'google_cloud_tts'],
        'generated_at' => now(),
        'is_active' => true,
    ]);

    $manualQuestion->forceFill([
        'question_audio_asset_id' => $audioAsset->id,
    ])->save();

    $seedQuestion = Question::factory()->for($module)->create([
        'seed_key' => 'hoeren-teil-1.reference.9',
        'content_key' => null,
        'topic' => 'Seeded reference question',
        'format' => 'listening_short_true_false',
        'content' => [
            'format' => 'listening_short_true_false',
            'transcript' => 'Sie hören jetzt einen kurzen Infoblock.',
        ],
    ]);

    $theme = QuestionGenerationTheme::factory()->hoerenTeil1()->create([
        'exam_slug' => $exam->slug,
        'module_slug' => $module->slug,
        'content_key' => null,
    ]);

    $this->artisan('content:export-catalog')
        ->assertSuccessful()
        ->expectsOutputToContain('Content catalog exported.');

    $manualQuestion->refresh();
    $seedQuestion->refresh();
    $theme->refresh();

    expect($manualQuestion->content_key)->not->toBeNull()
        ->and($seedQuestion->content_key)->toBeNull()
        ->and($theme->content_key)->not->toBeNull();

    $catalog = json_decode((string) File::get((string) config('content_catalog.path')), true, flags: JSON_THROW_ON_ERROR);

    expect($catalog['questions'])->toHaveCount(2)
        ->and(collect($catalog['questions'])->contains(fn (array $question): bool => $question['content_key'] === $manualQuestion->content_key && $question['audio_asset']['transcript_hash'] === $audioAsset->transcript_hash))->toBeTrue()
        ->and(collect($catalog['questions'])->contains(fn (array $question): bool => $question['seed_key'] === 'hoeren-teil-1.reference.9'))->toBeTrue()
        ->and($catalog['question_generation_themes'][0]['content_key'])->toBe($theme->content_key);
});

it('refreshes managed content from the catalog and marks mismatched listening audio as stale', function () {
    $user = User::factory()->create();

    $exam = Exam::factory()->create([
        'slug' => 'telc-b2',
        'name' => 'B2 Allgemein',
        'level' => 'B2',
    ]);

    $module = Module::factory()->for($exam)->create([
        'slug' => 'hoeren-teil-1',
        'name' => 'Hören Teil 1',
        'type' => 'listening',
        'default_points' => 1.5,
    ]);

    $question = Question::factory()->for($module)->create([
        'seed_key' => null,
        'content_key' => null,
        'topic' => 'Regionalnachrichten am Mittag',
        'format' => 'listening_segmented_true_false',
        'content' => [
            'format' => 'listening_segmented_true_false',
            'intro' => ['text' => 'Guten Tag.'],
            'segments' => [],
            'transcript' => 'Guten Tag. Sie hören die Regionalnachrichten.',
        ],
        'audio_source_type' => Question::AUDIO_SOURCE_ASSET,
        'question_audio_asset_id' => null,
    ]);

    $audioAsset = QuestionAudioAsset::query()->create([
        'label' => 'regionalnachrichten',
        'path' => 'question-audio/generated/regionalnachrichten.wav',
        'disk' => 'public',
        'original_name' => 'regionalnachrichten.wav',
        'transcript_hash' => hash('sha256', 'Guten Tag. Sie hören die Regionalnachrichten.'),
        'generation_metadata' => ['provider' => 'google_cloud_tts'],
        'generated_at' => now(),
        'is_active' => true,
    ]);

    $question->forceFill([
        'question_audio_asset_id' => $audioAsset->id,
    ])->save();

    $theme = QuestionGenerationTheme::factory()->hoerenTeil1()->create([
        'exam_slug' => $exam->slug,
        'module_slug' => $module->slug,
        'content_key' => null,
        'title' => 'Nachrichten aus der Region',
    ]);

    $this->artisan('content:export-catalog')->assertSuccessful();

    $question->refresh();
    $theme->refresh();

    $catalog = json_decode((string) File::get((string) config('content_catalog.path')), true, flags: JSON_THROW_ON_ERROR);

    $catalog['questions'][0]['content']['transcript'] = 'Neue längere Regionalnachrichten mit aktualisiertem Inhalt.';
    $catalog['questions'][0]['content']['intro']['text'] = 'Neue längere Regionalnachrichten.';

    File::put(
        (string) config('content_catalog.path'),
        (string) json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    $question->delete();
    $theme->delete();

    $driftQuestion = Question::factory()->for($module)->create([
        'seed_key' => null,
        'content_key' => null,
        'topic' => 'Kurze Meldungen alt',
        'format' => 'listening_short_true_false',
        'content' => [
            'format' => 'listening_short_true_false',
            'transcript' => 'Sie hören jetzt einen kurzen Infoblock.',
        ],
    ]);

    $driftTheme = QuestionGenerationTheme::factory()->hoerenTeil1()->create([
        'exam_slug' => $exam->slug,
        'module_slug' => $module->slug,
        'content_key' => null,
        'title' => 'Veraltete Theme-Kopie',
    ]);

    $this->artisan('content:refresh-from-catalog')
        ->assertSuccessful()
        ->expectsOutputToContain('Content catalog refreshed.');

    $syncedQuestion = Question::query()->where('content_key', $catalog['questions'][0]['content_key'])->firstOrFail();
    $syncedTheme = QuestionGenerationTheme::query()->where('content_key', $catalog['question_generation_themes'][0]['content_key'])->firstOrFail();

    expect($syncedQuestion->content['transcript'])->toBe('Neue längere Regionalnachrichten mit aktualisiertem Inhalt.')
        ->and($syncedQuestion->hasStaleListeningAudioAsset())->toBeTrue()
        ->and($syncedQuestion->resolveAudioUrl())->toBeNull()
        ->and(Question::query()->find($driftQuestion->id))->toBeNull()
        ->and(QuestionGenerationTheme::query()->find($driftTheme->id))->toBeNull()
        ->and($syncedTheme->title)->toBe('Nachrichten aus der Region')
        ->and(User::query()->find($user->id))->not->toBeNull();
});
