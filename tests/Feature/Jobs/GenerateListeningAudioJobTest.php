<?php

use App\Jobs\GenerateListeningAudioJob;
use App\Models\Question;
use App\Services\ListeningQuestionAudioSynthesisService;

it('rethrows synthesis errors and resets generation mode to manual', function () {
    $question = Question::factory()->create([
        'generation_mode' => Question::GENERATION_MODE_AI_AUDIO_GENERATING,
        'question_audio_asset_id' => null,
    ]);

    $service = mock(ListeningQuestionAudioSynthesisService::class);
    $service
        ->shouldReceive('synthesizeForQuestion')
        ->once()
        ->withArgs(function (Question $freshQuestion) use ($question): bool {
            return $freshQuestion->is($question);
        })
        ->andThrow(new RuntimeException('audio synthesis failed'));

    $job = new GenerateListeningAudioJob($question, Question::GENERATION_MODE_AI_DRAFT);

    expect(fn () => $job->handle($service))->toThrow(RuntimeException::class, 'audio synthesis failed');

    $question->refresh();

    expect($question->generation_mode)->toBe(Question::GENERATION_MODE_MANUAL)
        ->and($question->question_audio_asset_id)->toBeNull();
});
