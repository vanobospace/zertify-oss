<?php

use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Services\GeminiLiveNativeAudioService;
use App\Services\GoogleCloudTextToSpeechService;
use App\Services\ListeningQuestionAudioSynthesisService;
use Illuminate\Support\Facades\Storage;

function fakeGeminiWaveBinary(int $frames = 256): string
{
    $pcm = '';

    for ($index = 0; $index < $frames; $index++) {
        $value = (int) round(sin($index / 9) * 9000);

        if ($value < 0) {
            $value += 0x10000;
        }

        $pcm .= pack('v', $value);
    }

    $channels = 1;
    $sampleRate = 24000;
    $bitsPerSample = 16;
    $byteRate = (int) ($sampleRate * $channels * ($bitsPerSample / 8));
    $blockAlign = (int) ($channels * ($bitsPerSample / 8));
    $dataSize = strlen($pcm);
    $chunkSize = 36 + $dataSize;

    return 'RIFF'
        .pack('V', $chunkSize)
        .'WAVE'
        .'fmt '
        .pack('V', 16)
        .pack('v', 1)
        .pack('v', $channels)
        .pack('V', $sampleRate)
        .pack('V', $byteRate)
        .pack('v', $blockAlign)
        .pack('v', $bitsPerSample)
        .'data'
        .pack('V', $dataSize)
        .$pcm;
}

function makeSegmentedTeilOneQuestion(): Question
{
    $exam = Exam::factory()->create(['slug' => 'telc-b2']);
    $module = Module::factory()->create([
        'exam_id' => $exam->id,
        'name' => 'Hören Teil 1',
        'slug' => 'hoeren-teil-1',
        'type' => 'listening',
    ]);

    return Question::factory()->create([
        'module_id' => $module->id,
        'format' => 'listening_segmented_true_false',
        'topic' => 'Hören Teil 1: Nachrichtensendung',
        'content' => [
            'format' => 'listening_segmented_true_false',
            'intro' => [
                'text' => 'Guten Abend. Sie hören jetzt fünf Meldungen aus den Regionen.',
                'voice_profile' => 'anchor_main',
            ],
            'segments' => [
                ['id' => 'segment_1', 'number' => 1, 'voice_profile' => 'news_a', 'segment_text' => 'Meldung eins.'],
                ['id' => 'segment_2', 'number' => 2, 'voice_profile' => 'news_b', 'segment_text' => 'Meldung zwei.'],
                ['id' => 'segment_3', 'number' => 3, 'voice_profile' => 'news_c', 'segment_text' => 'Meldung drei.'],
                ['id' => 'segment_4', 'number' => 4, 'voice_profile' => 'news_d', 'segment_text' => 'Meldung vier.'],
                ['id' => 'segment_5', 'number' => 5, 'voice_profile' => 'news_e', 'segment_text' => 'Meldung fünf.'],
            ],
            'transcript' => implode("\n\n", [
                'Guten Abend. Sie hören jetzt fünf Meldungen aus den Regionen.',
                'Meldung eins.',
                'Meldung zwei.',
                'Meldung drei.',
                'Meldung vier.',
                'Meldung fünf.',
            ]),
            'statements' => [
                ['id' => 'statement_1', 'number' => 1, 'text' => 'A'],
                ['id' => 'statement_2', 'number' => 2, 'text' => 'B'],
                ['id' => 'statement_3', 'number' => 3, 'text' => 'C'],
                ['id' => 'statement_4', 'number' => 4, 'text' => 'D'],
                ['id' => 'statement_5', 'number' => 5, 'text' => 'E'],
            ],
            'correct' => [
                'statement_1' => 'true',
                'statement_2' => 'false',
                'statement_3' => 'true',
                'statement_4' => 'false',
                'statement_5' => 'true',
            ],
            'audio' => ['title' => 'Nachrichtensendung'],
        ],
        'is_active' => false,
        'audio_voice_preset' => Question::AUDIO_VOICE_PRESET_NEWS_FEMALE,
        'audio_style_preset' => Question::AUDIO_STYLE_PRESET_CLEAN,
    ]);
}

function makeTeilTwoLongQuestion(): Question
{
    $exam = Exam::factory()->create(['slug' => 'telc-b2']);
    $module = Module::factory()->create([
        'exam_id' => $exam->id,
        'name' => 'Hören Teil 2',
        'slug' => 'hoeren-teil-2',
        'type' => 'listening',
    ]);

    return Question::factory()->create([
        'module_id' => $module->id,
        'format' => 'listening_long_true_false',
        'topic' => 'Hören Teil 2: Interview',
        'content' => [
            'format' => 'listening_long_true_false',
            'transcript' => 'Heute sprechen wir über ein neues Stadtprojekt.',
            'audio' => ['title' => 'Interview'],
            'statements' => [['id' => 'statement_1', 'number' => 1, 'text' => 'A']],
            'correct' => ['statement_1' => 'true'],
        ],
        'is_active' => false,
        'audio_voice_preset' => Question::AUDIO_VOICE_PRESET_NEWS_FEMALE,
        'audio_style_preset' => Question::AUDIO_STYLE_PRESET_CLEAN,
    ]);
}

function makeTeilTwoLongDialogueQuestion(string $voicePreset = Question::AUDIO_VOICE_PRESET_DIALOG_MF): Question
{
    $exam = Exam::factory()->create(['slug' => 'telc-b2']);
    $module = Module::factory()->create([
        'exam_id' => $exam->id,
        'name' => 'Hören Teil 2',
        'slug' => 'hoeren-teil-2',
        'type' => 'listening',
    ]);

    return Question::factory()->create([
        'module_id' => $module->id,
        'format' => 'listening_long_true_false',
        'topic' => 'Hören Teil 2: Rundfunk-Interview',
        'content' => [
            'format' => 'listening_long_true_false',
            'transcript' => implode("\n", [
                'Moderator: Guten Abend und willkommen zum Rundfunk-Interview.',
                'Gast: Vielen Dank für die Einladung.',
                'Moderator: Welche Veränderungen sehen Sie im Alltag?',
                'Gast: Die Teams planen heute früher gemeinsam.',
            ]),
            'audio' => ['title' => 'Rundfunk-Interview'],
            'statements' => [['id' => 'statement_1', 'number' => 1, 'text' => 'A']],
            'correct' => ['statement_1' => 'true'],
        ],
        'is_active' => false,
        'audio_voice_preset' => $voicePreset,
        'audio_style_preset' => Question::AUDIO_STYLE_PRESET_CLEAN,
    ]);
}

describe('listening question synthesis with gemini native audio', function () {
    beforeEach(function () {
        Storage::fake('public');
        config()->set('services.speech.storage_disk', 'public');
        config()->set('services.speech.default_output_format', 'wav');
        config()->set('services.speech.gemini_live_native_audio.enabled_for_hoeren_teil1', true);
        config()->set('services.speech.gemini_live_native_audio.soft_cap_prompt_tokens', 1);
        config()->set('services.speech.hoeren_teil1_effects.enabled', true);
        config()->set('services.speech.hoeren_teil1_effects.intro_signal_enabled', true);
        config()->set('services.speech.hoeren_teil1_effects.final_gong_enabled', true);
        config()->set('services.speech.hoeren_teil1_effects.segment_pause_ms', 550);
    });

    it('uses gemini native audio for hoeren teil 1 segmented format and stores metadata', function () {
        $question = makeSegmentedTeilOneQuestion();

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldNotReceive('synthesize');
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        $geminiMock = Mockery::mock(GeminiLiveNativeAudioService::class);
        $geminiMock->shouldReceive('synthesize')
            ->times(6)
            ->andReturnUsing(function (): array {
                return [
                    'binary' => fakeGeminiWaveBinary(),
                    'extension' => 'wav',
                    'mime_type' => 'audio/wav',
                    'metadata' => [
                        'provider' => 'gemini_live_native_audio',
                        'model' => 'gemini-2.5-flash-preview-tts',
                        'latency_ms' => 95,
                        'usage' => [
                            'prompt_token_count' => 9,
                            'candidates_token_count' => 3,
                            'total_token_count' => 12,
                        ],
                    ],
                ];
            });
        app()->instance(GeminiLiveNativeAudioService::class, $geminiMock);

        $asset = app(ListeningQuestionAudioSynthesisService::class)->synthesizeForQuestion($question);
        $question->refresh();
        $asset->refresh();

        expect($question->audio_source_type)->toBe(Question::AUDIO_SOURCE_ASSET)
            ->and($question->question_audio_asset_id)->toBe($asset->id)
            ->and($asset->generation_metadata)->toMatchArray([
                'provider' => 'gemini_live_native_audio',
                'model' => 'gemini-2.5-flash-preview-tts',
                'question_format' => 'listening_segmented_true_false',
                'voice_preset' => Question::AUDIO_VOICE_PRESET_NEWS_FEMALE,
                'cost_cap_soft_warn' => true,
            ])
            ->and($asset->generation_metadata['usage']['total_token_count'] ?? null)->toBeInt();
        expect($asset->generation_metadata['effects_profile'] ?? null)->toMatchArray([
            'profile' => 'hoeren_teil1_canon',
            'enabled' => true,
            'applied' => true,
            'intro_signal_enabled' => true,
            'final_gong_enabled' => true,
        ]);

        Storage::disk('public')->assertExists((string) $asset->path);
    });

    it('falls back to google tts when gemini native audio quota is exceeded for hoeren teil 1', function () {
        $question = makeSegmentedTeilOneQuestion();

        $geminiMock = Mockery::mock(GeminiLiveNativeAudioService::class);
        $geminiMock->shouldReceive('synthesize')
            ->once()
            ->andThrow(new RuntimeException('Gemini native audio request failed: HTTP request returned status code 429: quota exceeded'));
        app()->instance(GeminiLiveNativeAudioService::class, $geminiMock);

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->once()
            ->withArgs(function (string $text, array $options): bool {
                return str_contains($text, 'Guten Abend.')
                    && ($options['provider'] ?? null) === 'google_cloud_tts'
                    && ($options['output_format'] ?? null) === 'wav';
            })
            ->andReturn([
                'binary' => fakeGeminiWaveBinary(),
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
                'metadata' => [
                    'provider' => 'google_cloud_tts',
                    'model' => 'de-DE-Chirp3-HD',
                ],
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        $asset = app(ListeningQuestionAudioSynthesisService::class)->synthesizeForQuestion($question);
        $asset->refresh();

        expect($asset->generation_metadata)->toMatchArray([
            'provider' => 'google_cloud_tts',
            'fallback_from_provider' => 'gemini_live_native_audio',
            'fallback_reason' => 'gemini_quota_exceeded_429',
            'question_format' => 'listening_segmented_true_false',
        ]);

        Storage::disk('public')->assertExists((string) $asset->path);
    });

    it('keeps teil 2 on google provider when teil 1 native audio is enabled', function () {
        $question = makeTeilTwoLongQuestion();

        $geminiMock = Mockery::mock(GeminiLiveNativeAudioService::class);
        $geminiMock->shouldNotReceive('synthesize');
        app()->instance(GeminiLiveNativeAudioService::class, $geminiMock);

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->once()
            ->withArgs(function (string $text, array $options): bool {
                return str_contains($text, 'Stadtprojekt')
                    && ($options['provider'] ?? null) === 'google_cloud_tts';
            })
            ->andReturn([
                'binary' => fakeGeminiWaveBinary(),
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        $asset = app(ListeningQuestionAudioSynthesisService::class)->synthesizeForQuestion($question);
        $asset->refresh();

        expect($asset->generation_metadata)->toMatchArray([
            'provider' => 'google_cloud_tts',
            'question_format' => 'listening_long_true_false',
        ])
            ->and(($asset->generation_metadata['cost_cap_soft_warn'] ?? null))->toBeFalse()
            ->and(($asset->generation_metadata['effects_profile']['applied'] ?? null))->toBeFalse();

        expect(QuestionAudioAsset::query()->whereKey($asset->id)->exists())->toBeTrue();
    });

    it('assembles teil 2 long format as a two-role dialogue when transcript has speaker turns', function () {
        $question = makeTeilTwoLongDialogueQuestion(Question::AUDIO_VOICE_PRESET_DIALOG_FM);

        $geminiMock = Mockery::mock(GeminiLiveNativeAudioService::class);
        $geminiMock->shouldNotReceive('synthesize');
        app()->instance(GeminiLiveNativeAudioService::class, $geminiMock);

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->times(4)
            ->withArgs(function (string $text, array $options): bool {
                return $text !== ''
                    && ($options['provider'] ?? null) === 'google_cloud_tts'
                    && in_array(($options['voice_preset'] ?? null), [
                        Question::AUDIO_VOICE_PRESET_ANCHOR_FEMALE,
                        Question::AUDIO_VOICE_PRESET_REPORTER_MALE,
                    ], true);
            })
            ->andReturn([
                'binary' => fakeGeminiWaveBinary(),
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        $asset = app(ListeningQuestionAudioSynthesisService::class)->synthesizeForQuestion($question);
        $asset->refresh();

        expect($asset->generation_metadata)->toMatchArray([
            'provider' => 'google_cloud_tts',
            'question_format' => 'listening_long_true_false',
            'voice_preset' => Question::AUDIO_VOICE_PRESET_DIALOG_FM,
        ])
            ->and($asset->generation_metadata['dialogue_pair_preset'] ?? null)->toBe(Question::AUDIO_VOICE_PRESET_DIALOG_FM)
            ->and(($asset->generation_metadata['clips'] ?? []))->toHaveCount(4);
    });

    it('maps legacy teil 2 voice presets to a dialog pair without failing', function () {
        $question = makeTeilTwoLongDialogueQuestion(Question::AUDIO_VOICE_PRESET_REPORTER_FEMALE);

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->times(4)
            ->andReturn([
                'binary' => fakeGeminiWaveBinary(),
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        $asset = app(ListeningQuestionAudioSynthesisService::class)->synthesizeForQuestion($question);
        $asset->refresh();

        expect($asset->generation_metadata['dialogue_pair_preset'] ?? null)->toBe(Question::AUDIO_VOICE_PRESET_DIALOG_MF)
            ->and($asset->generation_metadata['dialogue_pair']['interviewer_voice_preset'] ?? null)->toBe(Question::AUDIO_VOICE_PRESET_ANCHOR_MALE)
            ->and($asset->generation_metadata['dialogue_pair']['guest_voice_preset'] ?? null)->toBe(Question::AUDIO_VOICE_PRESET_REPORTER_FEMALE);
    });
});
