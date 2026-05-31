<?php

use App\Filament\Resources\ListeningQuestionResource\Pages\CreateListeningQuestion;
use App\Filament\Resources\ListeningQuestionResource\Pages\EditListeningQuestion;
use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Models\User;
use App\Services\GoogleCloudTextToSpeechService;
use Filament\Facades\Filament;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function authenticateListeningAdmin(): User
{
    $user = User::factory()->admin()->create([
        'email' => 'listening-admin@zertify.app',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    test()->actingAs($user);

    return $user;
}

function makeListeningQuestion(array $contentOverrides = [], string $format = 'listening_short_true_false'): Question
{
    $exam = Exam::factory()->create([
        'slug' => 'telc-b2',
    ]);
    $module = Module::factory()->create([
        'exam_id' => $exam->id,
        'name' => 'Hören Teil 1',
        'slug' => 'hoeren-teil-1',
        'type' => 'listening',
    ]);

    $content = $format === 'listening_segmented_true_false'
        ? array_replace_recursive([
            'format' => 'listening_segmented_true_false',
            'instructions' => 'Sie hören nun eine Nachrichtensendung. Dazu sollen Sie fünf Aufgaben lösen. Sie hören die Nachrichtensendung nur einmal.',
            'audio' => [
                'title' => 'Stadtnachrichten am Abend',
                'audio_notes' => 'Nachrichtensendung mit neutraler Sprecherstimme und fünf klar getrennten Meldungen aus den Regionen.',
            ],
            'intro' => [
                'text' => 'Guten Abend. Sie hören jetzt fünf Meldungen aus den Regionen.',
                'voice_profile' => 'anchor_main',
            ],
            'segments' => [
                ['id' => 'segment_1', 'number' => 1, 'voice_profile' => 'news_main', 'segment_text' => 'Der Wochenmarkt findet morgen wegen Regens in der Stadthalle statt.', 'statement_id' => 'statement_1', 'statement_text' => 'Der Wochenmarkt ist morgen in einer Halle.', 'correct_answer' => 'true', 'reason' => 'Die Stadthalle wird ausdrücklich genannt.', 'evidence' => 'in der Stadthalle'],
                ['id' => 'segment_2', 'number' => 2, 'voice_profile' => 'news_main', 'segment_text' => 'Die Buslinie 7 fährt wegen einer Baustelle erst ab Dienstag wieder normal.', 'statement_id' => 'statement_2', 'statement_text' => 'Die Buslinie 7 fährt schon heute wieder normal.', 'correct_answer' => 'false', 'reason' => 'Normalbetrieb erst ab Dienstag.', 'evidence' => 'erst ab Dienstag wieder normal'],
                ['id' => 'segment_3', 'number' => 3, 'voice_profile' => 'news_main', 'segment_text' => 'Im Hallenbad beginnt am Freitag ein zusätzlicher Schwimmkurs für Erwachsene.', 'statement_id' => 'statement_3', 'statement_text' => 'Ab Freitag gibt es einen weiteren Schwimmkurs für Erwachsene.', 'correct_answer' => 'true', 'reason' => 'Ein zusätzlicher Kurs wird genannt.', 'evidence' => 'beginnt am Freitag ein zusätzlicher Schwimmkurs'],
                ['id' => 'segment_4', 'number' => 4, 'voice_profile' => 'news_main', 'segment_text' => 'Für das Stadtfest am Samstag werden noch Helfer für den Getränkestand gesucht.', 'statement_id' => 'statement_4', 'statement_text' => 'Für das Stadtfest werden keine weiteren Helfer benötigt.', 'correct_answer' => 'false', 'reason' => 'Es werden noch Helfer gesucht.', 'evidence' => 'werden noch Helfer gesucht'],
                ['id' => 'segment_5', 'number' => 5, 'voice_profile' => 'news_main', 'segment_text' => 'Die Fahrradwerkstatt am Bahnhof öffnet in dieser Woche erst um zehn Uhr.', 'statement_id' => 'statement_5', 'statement_text' => 'Die Fahrradwerkstatt öffnet diese Woche später als sonst.', 'correct_answer' => 'true', 'reason' => 'Sie öffnet erst um zehn Uhr.', 'evidence' => 'öffnet in dieser Woche erst um zehn Uhr'],
            ],
            'transcript' => implode("\n\n", [
                'Guten Abend. Sie hören jetzt fünf Meldungen aus den Regionen.',
                'Der Wochenmarkt findet morgen wegen Regens in der Stadthalle statt.',
                'Die Buslinie 7 fährt wegen einer Baustelle erst ab Dienstag wieder normal.',
                'Im Hallenbad beginnt am Freitag ein zusätzlicher Schwimmkurs für Erwachsene.',
                'Für das Stadtfest am Samstag werden noch Helfer für den Getränkestand gesucht.',
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
                'statement_5' => ['correct_answer' => 'true', 'reason' => 'Die Werkstatt öffnet erst um zehn Uhr.', 'evidence' => 'erst um zehn Uhr'],
            ],
        ], $contentOverrides)
        : array_replace_recursive([
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
            'explanation' => [
                'statement_1' => ['correct_answer' => 'true', 'reason' => 'Die Halle wird ausdrücklich genannt.', 'evidence' => 'Markthalle'],
                'statement_2' => ['correct_answer' => 'false', 'reason' => 'Die Brücke bleibt bis Montag geschlossen.', 'evidence' => 'bis Montag geschlossen'],
                'statement_3' => ['correct_answer' => 'true', 'reason' => 'Zusätzliche Sitzplätze werden aufgebaut.', 'evidence' => 'zusätzliche Sitzplätze'],
                'statement_4' => ['correct_answer' => 'false', 'reason' => 'Der Kurs beginnt bereits morgen.', 'evidence' => 'bereits morgen'],
                'statement_5' => ['correct_answer' => 'true', 'reason' => 'Freiwillige werden gesucht.', 'evidence' => 'Freiwillige für eine Pflanzaktion'],
            ],
        ], $contentOverrides);

    return Question::factory()->create([
        'module_id' => $module->id,
        'format' => $format,
        'topic' => 'Hören 1: Meldungen aus dem Stadtmagazin',
        'content' => $content,
        'is_active' => false,
        'audio_voice_preset' => Question::AUDIO_VOICE_PRESET_NEWS_FEMALE,
        'audio_style_preset' => Question::AUDIO_STYLE_PRESET_CLEAN,
        'audio_source_type' => null,
        'question_audio_asset_id' => null,
    ]);
}

function fakeWaveBinary(string $pcm = ''): string
{
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

function fakePcmPattern(int $frames = 512): string
{
    $binary = '';

    for ($index = 0; $index < $frames; $index++) {
        $value = (int) round(sin($index / 11) * 12000);

        if ($value < 0) {
            $value += 0x10000;
        }

        $binary .= pack('v', $value);
    }

    return $binary;
}

function fakeGoogleServiceAccountJson(): string
{
    $privateKeyResource = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    if ($privateKeyResource === false) {
        throw new RuntimeException('Failed to generate a fake Google service account private key for tests.');
    }

    $privateKey = '';
    $exported = openssl_pkey_export($privateKeyResource, $privateKey);

    if (! $exported || $privateKey === '') {
        throw new RuntimeException('Failed to export a fake Google service account private key for tests.');
    }

    return json_encode([
        'client_email' => 'zertify-tts@test-project.iam.gserviceaccount.com',
        'private_key' => $privateKey,
    ], JSON_THROW_ON_ERROR);
}

describe('listening question audio generation', function () {
    beforeEach(function () {
        authenticateListeningAdmin();
        Storage::fake('public');
        config()->set('services.speech.storage_disk', 'public');
        config()->set('services.speech.default_output_format', 'wav');
    });

    it('exposes listening voice preset options on the model', function () {
        expect(Question::audioVoicePresetOptions())->toBe([
            Question::AUDIO_VOICE_PRESET_NEWS_MALE => 'News Male',
            Question::AUDIO_VOICE_PRESET_NEWS_FEMALE => 'News Female',
            Question::AUDIO_VOICE_PRESET_NEUTRAL_MALE => 'Neutral Male',
            Question::AUDIO_VOICE_PRESET_NEUTRAL_FEMALE => 'Neutral Female',
            Question::AUDIO_VOICE_PRESET_ANCHOR_FEMALE => 'Anchor Female',
            Question::AUDIO_VOICE_PRESET_ANCHOR_MALE => 'Anchor Male',
            Question::AUDIO_VOICE_PRESET_REPORTER_FEMALE => 'Reporter Female',
            Question::AUDIO_VOICE_PRESET_REPORTER_MALE => 'Reporter Male',
            Question::AUDIO_VOICE_PRESET_DIALOG_MF => 'Dialog M/F',
            Question::AUDIO_VOICE_PRESET_DIALOG_FM => 'Dialog F/M',
            Question::AUDIO_VOICE_PRESET_DIALOG_MM => 'Dialog M/M',
            Question::AUDIO_VOICE_PRESET_DIALOG_FF => 'Dialog F/F',
        ]);
    });

    it('exposes listening audio style preset options on the model', function () {
        expect(Question::audioStylePresetOptions())->toBe([
            Question::AUDIO_STYLE_PRESET_CLEAN => 'Clean',
            Question::AUDIO_STYLE_PRESET_NEWS_POLISH => 'News Polish',
            Question::AUDIO_STYLE_PRESET_RADIO_LIGHT => 'Radio Light',
            Question::AUDIO_STYLE_PRESET_RADIO_HEAVY => 'Radio Heavy',
            Question::AUDIO_STYLE_PRESET_PHONE_HOTLINE => 'Phone Hotline',
            Question::AUDIO_STYLE_PRESET_PODCAST_WARM => 'Podcast Warm',
            Question::AUDIO_STYLE_PRESET_FM_CLEAN => 'FM Clean',
            Question::AUDIO_STYLE_PRESET_PA_SPEAKER => 'PA Speaker',
            Question::AUDIO_STYLE_PRESET_ROOM_LIGHT => 'Room Light',
        ]);
    });

    it('routes listening audio generation through google cloud tts', function () {
        $question = makeListeningQuestion();

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->once()
            ->andReturn([
                'binary' => fakeWaveBinary(),
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->callAction('generate_audio')
            ->assertNotified();

        $question->refresh();
        $asset = QuestionAudioAsset::query()->find($question->question_audio_asset_id);

        expect($asset)->not->toBeNull()
            ->and($question->audio_source_type)->toBe(Question::AUDIO_SOURCE_ASSET)
            ->and((string) $asset?->original_name)->toEndWith('.wav')
            ->and($question->resolveAudioUrl())->not->toBeNull();

        Storage::disk('public')->assertExists((string) $asset?->path);
    });

    it('uses the selected voice preset for google cloud tts generation', function () {
        $question = makeListeningQuestion();
        $question->forceFill([
            'audio_voice_preset' => Question::AUDIO_VOICE_PRESET_NEUTRAL_MALE,
        ])->save();

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->once()
            ->withArgs(function (string $text, array $options): bool {
                return str_contains($text, 'Guten Morgen.')
                    && ($options['provider'] ?? null) === 'google_cloud_tts'
                    && ($options['voice_preset'] ?? null) === Question::AUDIO_VOICE_PRESET_NEUTRAL_MALE
                    && ($options['voice'] ?? null) === 'de-DE-Chirp3-HD-Orus'
                    && ($options['output_format'] ?? null) === 'wav';
            })
            ->andReturn([
                'binary' => fakeWaveBinary(),
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->callAction('generate_audio')
            ->assertNotified();

        $question->refresh();

        expect($question->question_audio_asset_id)->not->toBeNull()
            ->and($question->audio_source_type)->toBe(Question::AUDIO_SOURCE_ASSET);
    });

    it('fails cleanly when the transcript is missing', function () {
        $question = makeListeningQuestion([
            'transcript' => '',
        ]);

        $mock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $mock->shouldNotReceive('synthesize');
        app()->instance(GoogleCloudTextToSpeechService::class, $mock);

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->callAction('generate_audio')
            ->assertNotified();

        $question->refresh();

        expect($question->question_audio_asset_id)->toBeNull()
            ->and(QuestionAudioAsset::query()->count())->toBe(0);
    });

    it('assembles a segmented teil 1 track into one final asset', function () {
        $question = makeListeningQuestion([], 'listening_segmented_true_false');

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->once()
            ->withArgs(function (string $text, array $options): bool {
                return str_contains($text, 'Guten Abend. Sie hören jetzt fünf Meldungen aus den Regionen.')
                    && str_contains($text, 'Die Fahrradwerkstatt am Bahnhof öffnet in dieser Woche erst um zehn Uhr.')
                    && ($options['provider'] ?? null) === 'google_cloud_tts'
                    && ($options['voice_profile'] ?? null) === 'anchor_main'
                    && ($options['output_format'] ?? null) === 'wav';
            })
            ->andReturn([
                'binary' => fakeWaveBinary(),
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->callAction('generate_audio')
            ->assertNotified();

        $question->refresh();
        $asset = QuestionAudioAsset::query()->find($question->question_audio_asset_id);

        expect($asset)->not->toBeNull()
            ->and($question->audio_source_type)->toBe(Question::AUDIO_SOURCE_ASSET)
            ->and((string) $asset?->original_name)->toEndWith('.wav')
            ->and($question->resolveAudioUrl())->not->toBeNull()
            ->and($question->content['audio']['url'] ?? null)->toBeString();

        Storage::disk('public')->assertExists((string) $asset?->path);
    });

    it('generates long listening audio through the active provider', function () {
        $question = makeListeningQuestion([
            'format' => 'listening_long_true_false',
            'transcript' => 'Guten Abend. Heute sprechen wir mit einer jungen Unternehmerin über ihren neuen Buchladen, die ersten schweren Monate und ihre Pläne für die Zukunft.',
        ], 'listening_long_true_false');

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->once()
            ->withArgs(function (string $text, array $options): bool {
                return str_contains($text, 'jungen Unternehmerin')
                    && ($options['provider'] ?? null) === 'google_cloud_tts'
                    && ($options['voice_preset'] ?? null) === Question::AUDIO_VOICE_PRESET_NEWS_FEMALE;
            })
            ->andReturn([
                'binary' => fakeWaveBinary(),
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->callAction('generate_audio')
            ->assertNotified();

        $question->refresh();

        expect($question->question_audio_asset_id)->not->toBeNull()
            ->and($question->audio_source_type)->toBe(Question::AUDIO_SOURCE_ASSET);
    });

    it('shows the voice preset field and hides legacy audio controls for listening formats', function () {
        $question = makeListeningQuestion();

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->assertFormFieldVisible('audio_voice_preset')
            ->assertFormFieldVisible('audio_style_preset')
            ->assertFormFieldHidden('format')
            ->assertFormFieldHidden('audio_source_type')
            ->assertFormFieldHidden('question_audio_asset_id')
            ->assertFormFieldHidden('audio_external_url');
    });

    it('hides format and audio source selectors on listening create page before module selection', function () {
        Livewire::test(CreateListeningQuestion::class)
            ->assertFormFieldHidden('format')
            ->assertFormFieldHidden('audio_source_type');
    });

    it('keeps voice preset visible for hoeren teil 1 when gemini native is enabled', function () {
        config()->set('services.speech.gemini_live_native_audio.enabled_for_hoeren_teil1', true);
        $question = makeListeningQuestion();

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->assertFormFieldVisible('audio_voice_preset')
            ->assertFormFieldVisible('audio_style_preset');
    });

    it('falls back to the configured Google voice when a non-google voice is passed', function () {
        config()->set('services.speech.google_cloud_tts.service_account_json', fakeGoogleServiceAccountJson());
        config()->set('services.speech.google_cloud_tts.service_account_json_path', null);
        config()->set('services.speech.google_cloud_tts.token_uri', 'https://oauth.example.test/token');
        config()->set('services.speech.google_cloud_tts.endpoint', 'https://tts.example.test/v1/text:synthesize');
        config()->set('services.speech.google_cloud_tts.voice_name', 'de-DE-Chirp3-HD-Kore');
        config()->set('services.speech.google_cloud_tts.language_code', 'de-DE');

        $capturedVoiceName = null;

        Http::fake([
            'https://oauth.example.test/token' => Http::response([
                'access_token' => 'google-test-token',
            ]),
            'https://tts.example.test/v1/text:synthesize' => function (Request $request) use (&$capturedVoiceName) {
                $capturedVoiceName = $request->data()['voice']['name'] ?? null;

                return Http::response([
                    'audioContent' => base64_encode(fakeWaveBinary()),
                ]);
            },
        ]);

        $result = app(GoogleCloudTextToSpeechService::class)->synthesize('Guten Tag. Dies ist ein kurzer Test.', [
            'voice' => 'not-a-google-voice',
            'output_format' => 'wav',
        ]);

        expect($capturedVoiceName)->toBe('de-DE-Chirp3-HD-Kore')
            ->and($result['extension'])->toBe('wav')
            ->and($result['mime_type'])->toBe('audio/wav')
            ->and($result['binary'])->toBeString();
    });

    it('applies the selected audio style when generating listening audio', function () {
        config()->set('services.speech.gemini_live_native_audio.enabled_for_hoeren_teil1', false);
        $question = makeListeningQuestion();

        $sourceBinary = fakeWaveBinary(fakePcmPattern());

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->once()
            ->andReturn([
                'binary' => $sourceBinary,
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->fillForm([
                'audio_style_preset' => Question::AUDIO_STYLE_PRESET_RADIO_LIGHT,
            ])
            ->callAction('generate_audio')
            ->assertNotified();

        $question->refresh();
        $asset = QuestionAudioAsset::query()->findOrFail($question->question_audio_asset_id);
        $storedBinary = Storage::disk('public')->get($asset->path);

        expect($storedBinary)->not->toBe($sourceBinary)
            ->and(substr($storedBinary, 0, 4))->toBe('RIFF')
            ->and($question->audio_source_type)->toBe(Question::AUDIO_SOURCE_ASSET)
            ->and($asset->transcript_hash)->toBe($question->currentListeningTranscriptHash())
            ->and($asset->generation_metadata)->toMatchArray([
                'style_preset' => Question::AUDIO_STYLE_PRESET_RADIO_LIGHT,
                'voice_preset' => Question::AUDIO_VOICE_PRESET_NEWS_FEMALE,
                'output_format' => 'wav',
                'transcript_hash' => $question->currentListeningTranscriptHash(),
            ]);

        expect(in_array(($asset->generation_metadata['provider'] ?? null), ['google_cloud_tts', 'gemini_live_native_audio'], true))->toBeTrue();
    });

    it('marks generated listening audio as stale when the transcript changes', function () {
        $question = makeListeningQuestion();

        $googleMock = Mockery::mock(GoogleCloudTextToSpeechService::class);
        $googleMock->shouldReceive('synthesize')
            ->once()
            ->andReturn([
                'binary' => fakeWaveBinary(),
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ]);
        app()->instance(GoogleCloudTextToSpeechService::class, $googleMock);

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->callAction('generate_audio')
            ->assertNotified();

        $question->refresh();

        expect($question->resolveAudioUrl())->not->toBeNull()
            ->and($question->hasFreshListeningAudioAsset())->toBeTrue();

        $content = $question->content;
        $content['transcript'] = 'Komplett neuer Transcript fuer den Test.';

        $question->forceFill([
            'content' => $content,
        ])->save();

        $question->refresh()->load('audioAsset');

        expect($question->hasStaleListeningAudioAsset())->toBeTrue()
            ->and($question->resolveAudioUrl())->toBeNull();
    });

    it('shows a stale audio warning in the edit form when the transcript no longer matches the asset', function () {
        $question = makeListeningQuestion();
        $audioAsset = QuestionAudioAsset::query()->create([
            'label' => 'Outdated listening audio',
            'disk' => 'public',
            'path' => 'question-audio/generated/outdated.wav',
            'original_name' => 'outdated.wav',
            'transcript_hash' => hash('sha256', 'older transcript'),
            'generation_metadata' => ['provider' => 'google_cloud_tts'],
            'is_active' => true,
        ]);

        $question->forceFill([
            'audio_source_type' => Question::AUDIO_SOURCE_ASSET,
            'question_audio_asset_id' => $audioAsset->id,
        ])->save();

        Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ])
            ->assertSee('Прикреплённое аудио устарело.')
            ->assertSee('Перегенерируйте аудио для обновления предпрослушивания.');
    });

    it('refreshes preview data after queued audio generation completes without manual reload', function () {
        $question = makeListeningQuestion();
        $question->update([
            'generation_mode' => Question::GENERATION_MODE_AI_AUDIO_GENERATING,
        ]);

        $component = Livewire::test(EditListeningQuestion::class, [
            'record' => $question->getKey(),
        ]);

        $audioAsset = QuestionAudioAsset::query()->create([
            'label' => 'Queued listening audio',
            'disk' => 'public',
            'path' => 'question-audio/generated/queued-preview.wav',
            'original_name' => 'queued-preview.wav',
            'transcript_hash' => $question->currentListeningTranscriptHash(),
            'generation_metadata' => ['provider' => 'google_cloud_tts'],
            'is_active' => true,
        ]);

        $updatedContent = is_array($question->content) ? $question->content : [];
        $updatedContent['audio']['url'] = $audioAsset->public_url;

        $question->forceFill([
            'question_audio_asset_id' => $audioAsset->id,
            'audio_source_type' => Question::AUDIO_SOURCE_ASSET,
            'generation_mode' => Question::GENERATION_MODE_MANUAL,
            'content' => $updatedContent,
        ])->save();

        $component
            ->call('checkGenerationStatus')
            ->assertSet('pendingGenerationType', null)
            ->assertSet('pendingAudioAssetId', null)
            ->assertSee((string) $audioAsset->public_url);
    });
});
