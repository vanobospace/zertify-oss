<?php

use App\Services\ListeningAudioPostProcessor;

function fakeProcessorWaveBinary(int $frames = 1200): string
{
    $pcm = '';

    for ($index = 0; $index < $frames; $index++) {
        $value = (int) round(sin($index / 8) * 8000);

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

describe('listening teil 1 canon effects profile', function () {
    it('applies intro and final gong markers in canonical operation order', function () {
        config()->set('services.speech.hoeren_teil1_effects.enabled', true);
        config()->set('services.speech.hoeren_teil1_effects.intro_signal_enabled', true);
        config()->set('services.speech.hoeren_teil1_effects.final_gong_enabled', true);
        config()->set('services.speech.hoeren_teil1_effects.effects_gain_db', -15.0);
        config()->set('services.speech.hoeren_teil1_effects.speech_target_lufs', -19.0);

        $input = [
            'binary' => fakeProcessorWaveBinary(),
            'extension' => 'wav',
            'mime_type' => 'audio/wav',
        ];

        $result = app(ListeningAudioPostProcessor::class)->processTeilOneCanonEffects($input);
        $output = $result['audio'];

        expect($result['effects'])->toMatchArray([
            'profile' => 'hoeren_teil1_canon',
            'enabled' => true,
            'applied' => true,
            'intro_signal_enabled' => true,
            'final_gong_enabled' => true,
        ])->and($result['effects']['operations'] ?? [])->toBe([
            'speech_normalization',
            'intro_signal',
            'final_gong',
            'final_normalization',
            'final_limiter',
        ]);

        expect(strlen($output['binary']))->toBeGreaterThan(strlen($input['binary']));
    });

    it('returns baseline audio when teil 1 effects are disabled', function () {
        config()->set('services.speech.hoeren_teil1_effects.enabled', false);

        $input = [
            'binary' => fakeProcessorWaveBinary(),
            'extension' => 'wav',
            'mime_type' => 'audio/wav',
        ];

        $result = app(ListeningAudioPostProcessor::class)->processTeilOneCanonEffects($input);

        expect($result['audio']['binary'])->toBe($input['binary'])
            ->and($result['effects'])->toMatchArray([
                'profile' => 'hoeren_teil1_canon',
                'enabled' => false,
                'applied' => false,
            ]);
    });
});

describe('listening audio style presets', function () {
    it('applies clearly different output for non-clean style presets', function () {
        $input = [
            'binary' => fakeProcessorWaveBinary(8000),
            'extension' => 'wav',
            'mime_type' => 'audio/wav',
        ];

        $processor = app(ListeningAudioPostProcessor::class);
        $clean = $processor->process($input, 'clean');
        $radioHeavy = $processor->process($input, 'radio_heavy');
        $phoneHotline = $processor->process($input, 'phone_hotline');

        expect($clean['binary'])->toBe($input['binary'])
            ->and($radioHeavy['binary'])->not->toBe($input['binary'])
            ->and($phoneHotline['binary'])->not->toBe($input['binary'])
            ->and($radioHeavy['binary'])->not->toBe($phoneHotline['binary']);
    });
});
