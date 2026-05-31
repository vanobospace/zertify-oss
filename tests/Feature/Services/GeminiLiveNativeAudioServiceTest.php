<?php

use App\Services\GeminiLiveNativeAudioService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function fakeNativeWaveBinary(): string
{
    return 'RIFF'.str_repeat("\x00", 64);
}

describe('gemini live native audio service', function () {
    beforeEach(function () {
        config()->set('services.speech.gemini_live_native_audio.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-tts:generateContent');
        config()->set('services.speech.gemini_live_native_audio.api_key', 'test-key');
        config()->set('services.speech.gemini_live_native_audio.model', 'gemini-2.5-flash-preview-tts');
        config()->set('services.speech.gemini_live_native_audio.mime_type', 'audio/wav');
    });

    it('returns synthesized audio and metadata on success', function () {
        $capturedVoice = null;

        Http::fake([
            '*' => function (Request $request) use (&$capturedVoice) {
                $capturedVoice = data_get($request->data(), 'generationConfig.speechConfig.voiceConfig.prebuiltVoiceConfig.voiceName');

                return Http::response([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'inlineData' => [
                                    'mimeType' => 'audio/wav',
                                    'data' => base64_encode(fakeNativeWaveBinary()),
                                ],
                            ]],
                        ],
                    ]],
                    'usageMetadata' => [
                        'promptTokenCount' => 11,
                        'candidatesTokenCount' => 7,
                        'totalTokenCount' => 18,
                    ],
                ]);
            },
        ]);

        $result = app(GeminiLiveNativeAudioService::class)->synthesize('Kurze Nachricht fuer den Test.', [
            'voice_profile' => 'news_main',
            'voice' => 'Kore',
        ]);

        expect($capturedVoice)->toBe('Kore')
            ->and($result['extension'])->toBe('wav')
            ->and($result['mime_type'])->toBe('audio/wav')
            ->and($result['binary'])->toBeString()
            ->and($result['metadata']['provider'] ?? null)->toBe('gemini_live_native_audio')
            ->and($result['metadata']['model'] ?? null)->toBe('gemini-2.5-flash-preview-tts')
            ->and($result['metadata']['usage']['total_token_count'] ?? null)->toBe(18);
    });

    it('falls back to default voice in payload when voice is not passed', function () {
        $capturedVoice = null;

        Http::fake([
            '*' => function (Request $request) use (&$capturedVoice) {
                $capturedVoice = data_get($request->data(), 'generationConfig.speechConfig.voiceConfig.prebuiltVoiceConfig.voiceName');

                return Http::response([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'inlineData' => [
                                    'mimeType' => 'audio/wav',
                                    'data' => base64_encode(fakeNativeWaveBinary()),
                                ],
                            ],
                            ]],
                    ]],
                ]);
            },
        ]);

        app(GeminiLiveNativeAudioService::class)->synthesize('Kurze Nachricht fuer den Test.');

        expect($capturedVoice)->toBe('Kore');
    });

    it('throws when response has no audio part', function () {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => 'No audio here',
                        ]],
                    ],
                ]],
            ]),
        ]);

        expect(fn () => app(GeminiLiveNativeAudioService::class)->synthesize('Test ohne Audio.'))
            ->toThrow(RuntimeException::class, 'did not include audio data');
    });

    it('throws on api errors', function () {
        Http::fake([
            '*' => Http::response([
                'error' => ['message' => 'Bad request'],
            ], 400),
        ]);

        expect(fn () => app(GeminiLiveNativeAudioService::class)->synthesize('Test mit API Fehler.'))
            ->toThrow(RuntimeException::class, 'request failed');
    });

    it('throws on timeout or connection failure', function () {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        expect(fn () => app(GeminiLiveNativeAudioService::class)->synthesize('Test mit Timeout.'))
            ->toThrow(RuntimeException::class, 'timed out or could not connect');
    });

    it('throws for unsupported mime type', function () {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'inlineData' => [
                                'mimeType' => 'audio/ogg',
                                'data' => base64_encode('ogg-data'),
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        expect(fn () => app(GeminiLiveNativeAudioService::class)->synthesize('Test mit ogg mime.'))
            ->toThrow(RuntimeException::class, 'Unsupported Gemini native audio mime type');
    });

    it('wraps linear pcm mime into wav automatically', function () {
        $pcm = pack('v*', 0, 1000, 0, 1000);

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'inlineData' => [
                                'mimeType' => 'audio/L16;codecs=pcm;rate=24000',
                                'data' => base64_encode($pcm),
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $result = app(GeminiLiveNativeAudioService::class)->synthesize('PCM test.');

        expect($result['extension'])->toBe('wav')
            ->and($result['mime_type'])->toBe('audio/wav')
            ->and(substr((string) $result['binary'], 0, 4))->toBe('RIFF');
    });
});
