<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ListeningAudioPostProcessor
{
    /**
     * @param  array{binary: string, extension: string, mime_type: string}  $audio
     * @return array{binary: string, extension: string, mime_type: string}
     */
    public function process(array $audio, string $stylePreset): array
    {
        if ($stylePreset === Question::AUDIO_STYLE_PRESET_CLEAN) {
            return $audio;
        }

        if (($audio['extension'] ?? null) !== 'wav') {
            return $audio;
        }

        $binary = (string) ($audio['binary'] ?? '');

        if ($binary === '') {
            return $audio;
        }

        $wav = $this->parseWav($binary);
        $styleConfig = $this->styleConfig($stylePreset);
        $processedData = $this->processPcmData(
            substr($binary, $wav['data_offset'], $wav['data_size']),
            $styleConfig,
        );
        $processedBinary = substr_replace($binary, $processedData, $wav['data_offset'], $wav['data_size']);

        return [
            ...$audio,
            'binary' => $processedBinary,
        ];
    }

    /**
     * @param  array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}  $audio
     * @return array{
     *     audio: array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>},
     *     effects: array<string, mixed>
     * }
     */
    public function processTeilOneCanonEffects(array $audio): array
    {
        $config = config('services.speech.hoeren_teil1_effects', []);

        if (! is_array($config) || ! (bool) ($config['enabled'] ?? false)) {
            return [
                'audio' => $audio,
                'effects' => [
                    'profile' => 'hoeren_teil1_canon',
                    'enabled' => false,
                    'applied' => false,
                ],
            ];
        }

        if (($audio['extension'] ?? null) !== 'wav') {
            return [
                'audio' => $audio,
                'effects' => [
                    'profile' => 'hoeren_teil1_canon',
                    'enabled' => true,
                    'applied' => false,
                    'reason' => 'non_wav_input',
                ],
            ];
        }

        $binary = (string) ($audio['binary'] ?? '');

        if ($binary === '') {
            return [
                'audio' => $audio,
                'effects' => [
                    'profile' => 'hoeren_teil1_canon',
                    'enabled' => true,
                    'applied' => false,
                    'reason' => 'empty_audio',
                ],
            ];
        }

        $wav = $this->parseWav($binary);
        $sampleRate = (int) (($wav['sample_rate'] ?? 24000));
        $effectsGainLinear = $this->dbToLinear((float) ($config['effects_gain_db'] ?? -16.0));
        $targetLufs = (float) ($config['speech_target_lufs'] ?? -18.5);
        $introEnabled = (bool) ($config['intro_signal_enabled'] ?? false);
        $gongEnabled = (bool) ($config['final_gong_enabled'] ?? true);
        $operations = ['speech_normalization'];

        $speechSamples = $this->decodeSamples(substr($binary, $wav['data_offset'], $wav['data_size']));
        $speechSamples = $this->normalizeToTargetLufs($speechSamples, $targetLufs);
        $resultSamples = $speechSamples;

        if ($introEnabled) {
            $introSignal = $this->generateIntroSignal($sampleRate, $effectsGainLinear);
            $resultSamples = [...$introSignal, ...$this->generateSilence($sampleRate, 60), ...$resultSamples];
            $operations[] = 'intro_signal';
        }

        if ($gongEnabled) {
            $resultSamples = [...$resultSamples, ...$this->generateSilence($sampleRate, 120), ...$this->generateFinalGong($sampleRate, $effectsGainLinear)];
            $operations[] = 'final_gong';
        }

        $resultSamples = $this->normalizeSamples($resultSamples, 0.92);
        $operations[] = 'final_normalization';
        $resultSamples = $this->applyPeakLimiter($resultSamples, 0.95);
        $operations[] = 'final_limiter';

        $processedBinary = $this->wrapPcmAsWav(
            $this->encodeSamples($resultSamples),
            $sampleRate,
            (int) ($wav['channels'] ?? 1),
            (int) ($wav['bits_per_sample'] ?? 16),
        );

        return [
            'audio' => [
                ...$audio,
                'binary' => $processedBinary,
            ],
            'effects' => [
                'profile' => 'hoeren_teil1_canon',
                'enabled' => true,
                'applied' => true,
                'intro_signal_enabled' => $introEnabled,
                'final_gong_enabled' => $gongEnabled,
                'effects_gain_db' => (float) ($config['effects_gain_db'] ?? -16.0),
                'speech_target_lufs' => $targetLufs,
                'operations' => $operations,
            ],
        ];
    }

    /**
     * @return array{
     *     data_offset: int,
     *     data_size: int,
     *     sample_rate: int,
     *     channels: int,
     *     bits_per_sample: int
     * }
     */
    private function parseWav(string $binary): array
    {
        if (strlen($binary) < 44 || substr($binary, 0, 4) !== 'RIFF' || substr($binary, 8, 4) !== 'WAVE') {
            throw new RuntimeException('Listening audio post-processing supports only RIFF/WAVE audio.');
        }

        $offset = 12;
        $format = null;
        $channels = null;
        $sampleRate = null;
        $bitsPerSample = null;
        $dataOffset = null;
        $dataSize = null;

        while ($offset + 8 <= strlen($binary)) {
            $chunkId = substr($binary, $offset, 4);
            $chunkSize = unpack('Vsize', substr($binary, $offset + 4, 4))['size'] ?? 0;
            $chunkDataOffset = $offset + 8;

            if ($chunkId === 'fmt ' && $chunkSize >= 16 && $chunkDataOffset + $chunkSize <= strlen($binary)) {
                $fmt = unpack('vformat/vchannels/VsampleRate/VbyteRate/vblockAlign/vbitsPerSample', substr($binary, $chunkDataOffset, 16));
                $format = (int) ($fmt['format'] ?? 0);
                $channels = (int) ($fmt['channels'] ?? 0);
                $sampleRate = (int) ($fmt['sampleRate'] ?? 0);
                $bitsPerSample = (int) ($fmt['bitsPerSample'] ?? 0);
            }

            if ($chunkId === 'data') {
                $dataOffset = $chunkDataOffset;
                $dataSize = (int) $chunkSize;
                break;
            }

            $offset = $chunkDataOffset + $chunkSize + ($chunkSize % 2);
        }

        if ($format !== 1 || $channels !== 1 || $sampleRate === null || $bitsPerSample !== 16 || $dataOffset === null || $dataSize === null) {
            throw new RuntimeException('Listening audio post-processing supports only mono 16-bit PCM WAV audio.');
        }

        if ($dataOffset + $dataSize > strlen($binary)) {
            throw new RuntimeException('Listening audio post-processing received a malformed WAV payload.');
        }

        return [
            'data_offset' => $dataOffset,
            'data_size' => $dataSize,
            'sample_rate' => $sampleRate,
            'channels' => $channels,
            'bits_per_sample' => $bitsPerSample,
        ];
    }

    /**
     * @param  array<string, float>  $styleConfig
     */
    private function processPcmData(string $pcmData, array $styleConfig): string
    {
        $samples = $this->decodeSamples($pcmData);

        if ($samples === []) {
            return $pcmData;
        }

        $normalized = $this->normalizeSamples($samples, $styleConfig['normalize_target_peak']);
        $processed = $this->applyDynamicShape($normalized, $styleConfig);

        return $this->encodeSamples($processed);
    }

    /**
     * @return list<float>
     */
    private function decodeSamples(string $pcmData): array
    {
        $values = unpack('v*', $pcmData);

        if (! is_array($values)) {
            return [];
        }

        $samples = [];

        foreach ($values as $value) {
            $signed = $value >= 0x8000 ? $value - 0x10000 : $value;
            $samples[] = max(-1.0, min(1.0, $signed / 32768));
        }

        return $samples;
    }

    /**
     * @param  list<float>  $samples
     * @return list<float>
     */
    private function normalizeSamples(array $samples, float $targetPeak): array
    {
        $peak = 0.0;

        foreach ($samples as $sample) {
            $peak = max($peak, abs($sample));
        }

        if ($peak <= 0.0) {
            return $samples;
        }

        $gain = min(1.0, $targetPeak / $peak);

        return array_map(
            static fn (float $sample): float => max(-1.0, min(1.0, $sample * $gain)),
            $samples,
        );
    }

    /**
     * @param  list<float>  $samples
     * @param  array<string, float>  $styleConfig
     * @return list<float>
     */
    private function applyDynamicShape(array $samples, array $styleConfig): array
    {
        $compressed = [];

        foreach ($samples as $sample) {
            $compressed[] = $this->compressSample(
                $sample,
                $styleConfig['compression_threshold'],
                $styleConfig['compression_ratio'],
            );
        }

        $highPassed = $this->highPass($compressed, $styleConfig['highpass_alpha']);
        $lowPassed = $this->lowPass($compressed, $styleConfig['lowpass_alpha']);

        $processed = [];
        $saturationDrive = max(1.0, (float) ($styleConfig['saturation_drive'] ?? 1.0));
        $noiseLevel = max(0.0, (float) ($styleConfig['noise_level'] ?? 0.0));
        $sampleHold = max(1, (int) round((float) ($styleConfig['sample_hold'] ?? 1.0)));
        $heldSample = 0.0;

        foreach ($compressed as $index => $sample) {
            $wet = $sample
                + ($highPassed[$index] * $styleConfig['highpass_mix'])
                - (($sample - $lowPassed[$index]) * $styleConfig['lowpass_mix']);

            $mixed = ($sample * $styleConfig['dry_mix'])
                + ($wet * $styleConfig['wet_mix']);

            $styled = $this->saturateSample($mixed * $styleConfig['makeup_gain'], $saturationDrive);

            if ($noiseLevel > 0.0) {
                $styled += $this->deterministicNoise($index) * $noiseLevel;
            }

            $clamped = max(-1.0, min(1.0, $styled));

            if ($sampleHold > 1) {
                if (($index % $sampleHold) === 0) {
                    $heldSample = $clamped;
                }

                $clamped = $heldSample;
            }

            $processed[] = $clamped;
        }

        return $processed;
    }

    private function compressSample(float $sample, float $threshold, float $ratio): float
    {
        if ($ratio <= 1.0 || abs($sample) <= $threshold) {
            return $sample;
        }

        $sign = $sample < 0 ? -1.0 : 1.0;
        $magnitude = abs($sample);
        $excess = $magnitude - $threshold;
        $compressedExcess = $excess / $ratio;

        return $sign * min(1.0, $threshold + $compressedExcess);
    }

    private function saturateSample(float $sample, float $drive): float
    {
        if ($drive <= 1.0) {
            return max(-1.0, min(1.0, $sample));
        }

        $normalizer = tanh($drive);

        if ($normalizer <= 0.0) {
            return max(-1.0, min(1.0, $sample));
        }

        return tanh($sample * $drive) / $normalizer;
    }

    private function deterministicNoise(int $index): float
    {
        $state = ($index * 1103515245 + 12345) & 0x7FFFFFFF;

        return (($state / 1073741824.0) - 1.0) * 0.5;
    }

    /**
     * @param  list<float>  $samples
     * @return list<float>
     */
    private function normalizeToTargetLufs(array $samples, float $targetLufs): array
    {
        if ($samples === []) {
            return $samples;
        }

        $rms = $this->calculateRms($samples);

        if ($rms <= 0.0) {
            return $samples;
        }

        $currentLufs = 20.0 * log10($rms);
        $gainDb = $targetLufs - $currentLufs;
        $gainLinear = $this->dbToLinear($gainDb);

        return $this->applyGain($samples, $gainLinear);
    }

    /**
     * @param  list<float>  $samples
     * @return list<float>
     */
    private function applyGain(array $samples, float $gainLinear): array
    {
        return array_map(
            static fn (float $sample): float => max(-1.0, min(1.0, $sample * $gainLinear)),
            $samples,
        );
    }

    /**
     * @param  list<float>  $samples
     */
    private function calculateRms(array $samples): float
    {
        if ($samples === []) {
            return 0.0;
        }

        $sumSquares = 0.0;

        foreach ($samples as $sample) {
            $sumSquares += $sample * $sample;
        }

        return sqrt($sumSquares / count($samples));
    }

    private function dbToLinear(float $db): float
    {
        return pow(10.0, $db / 20.0);
    }

    /**
     * @return list<float>
     */
    private function generateSilence(int $sampleRate, int $milliseconds): array
    {
        $frames = max(0, (int) round($sampleRate * ($milliseconds / 1000)));

        return array_fill(0, $frames, 0.0);
    }

    /**
     * @return list<float>
     */
    private function generateIntroSignal(int $sampleRate, float $gainLinear): array
    {
        return $this->generateTone(
            sampleRate: $sampleRate,
            frequencyHz: 990.0,
            durationMs: 130,
            gainLinear: $gainLinear,
            withDecay: true,
        );
    }

    /**
     * @return list<float>
     */
    private function generateFinalGong(int $sampleRate, float $gainLinear): array
    {
        $first = $this->generateTone(
            sampleRate: $sampleRate,
            frequencyHz: 780.0,
            durationMs: 300,
            gainLinear: $gainLinear,
            withDecay: true,
        );
        $second = $this->generateTone(
            sampleRate: $sampleRate,
            frequencyHz: 580.0,
            durationMs: 420,
            gainLinear: $gainLinear * 0.9,
            withDecay: true,
        );

        return [...$first, ...$second];
    }

    /**
     * @return list<float>
     */
    private function generateTone(int $sampleRate, float $frequencyHz, int $durationMs, float $gainLinear, bool $withDecay): array
    {
        $frames = max(1, (int) round($sampleRate * ($durationMs / 1000)));
        $samples = [];

        for ($index = 0; $index < $frames; $index++) {
            $phase = 2.0 * M_PI * $frequencyHz * ($index / $sampleRate);
            $amplitude = $withDecay ? (1.0 - ($index / $frames)) : 1.0;
            $samples[] = max(-1.0, min(1.0, sin($phase) * $gainLinear * $amplitude));
        }

        return $samples;
    }

    /**
     * @param  list<float>  $samples
     * @return list<float>
     */
    private function applyPeakLimiter(array $samples, float $threshold): array
    {
        return array_map(
            static function (float $sample) use ($threshold): float {
                if ($sample > $threshold) {
                    return $threshold + (($sample - $threshold) * 0.35);
                }

                if ($sample < -$threshold) {
                    return -$threshold + (($sample + $threshold) * 0.35);
                }

                return $sample;
            },
            $samples,
        );
    }

    private function wrapPcmAsWav(string $pcm, int $sampleRate, int $channels, int $bitsPerSample): string
    {
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

    /**
     * @param  list<float>  $samples
     * @return list<float>
     */
    private function highPass(array $samples, float $alpha): array
    {
        if ($alpha <= 0.0) {
            return array_fill(0, count($samples), 0.0);
        }

        $output = [];
        $previousInput = 0.0;
        $previousOutput = 0.0;

        foreach ($samples as $sample) {
            $current = $alpha * ($previousOutput + $sample - $previousInput);
            $output[] = $current;
            $previousInput = $sample;
            $previousOutput = $current;
        }

        return $output;
    }

    /**
     * @param  list<float>  $samples
     * @return list<float>
     */
    private function lowPass(array $samples, float $alpha): array
    {
        if ($alpha <= 0.0) {
            return $samples;
        }

        $output = [];
        $previous = 0.0;

        foreach ($samples as $sample) {
            $previous = $previous + $alpha * ($sample - $previous);
            $output[] = $previous;
        }

        return $output;
    }

    /**
     * @param  list<float>  $samples
     */
    private function encodeSamples(array $samples): string
    {
        $binary = '';

        foreach ($samples as $sample) {
            $value = (int) round(max(-1.0, min(1.0, $sample)) * 32767);

            if ($value < 0) {
                $value += 0x10000;
            }

            $binary .= pack('v', $value);
        }

        return $binary;
    }

    /**
     * @return array<string, float>
     */
    private function styleConfig(string $stylePreset): array
    {
        $config = config("services.speech.audio_style_presets.{$stylePreset}");

        if (! is_array($config)) {
            Log::warning('Unknown listening audio style preset. Falling back to clean.', [
                'requested_style_preset' => $stylePreset,
            ]);

            $config = config('services.speech.audio_style_presets.clean');
        }

        if (! is_array($config)) {
            throw new RuntimeException('Listening audio style preset [clean] is not configured.');
        }

        return array_map(
            static fn (mixed $value): float => (float) $value,
            $config,
        );
    }
}
