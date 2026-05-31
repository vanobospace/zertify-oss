<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiLiveNativeAudioService implements SpeechSynthesisProvider
{
    public function key(): string
    {
        return 'gemini_live_native_audio';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}
     */
    public function synthesize(string $text, array $options = []): array
    {
        $trimmedText = trim($text);

        if ($trimmedText === '') {
            throw new RuntimeException('Transcript is required for Gemini native audio synthesis.');
        }

        $startedAt = microtime(true);
        $response = $this->request($this->payload($trimmedText, $options), $options);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Gemini native audio request failed: '.$e->getMessage(), previous: $e);
        }

        $audioBase64 = $this->resolveAudioBase64($response->json());

        if ($audioBase64 === '') {
            throw new RuntimeException('Gemini native audio response did not include audio data.');
        }

        $binary = base64_decode($audioBase64, true);

        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('Gemini native audio returned invalid base64 payload.');
        }

        $mimeType = $this->resolveMimeType($response->json(), (string) ($options['mime_type'] ?? 'audio/wav'));

        if ($this->isLinearPcmMime($mimeType)) {
            $binary = $this->wrapPcm16ToWav($binary, $this->sampleRateFromMime($mimeType));
            $mimeType = 'audio/wav';
        }

        $extension = $this->extensionForMime($mimeType);

        return [
            'binary' => $binary,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'metadata' => [
                'provider' => $this->key(),
                'model' => (string) ($options['model'] ?? config('services.speech.gemini_live_native_audio.model', 'gemini-2.5-flash-preview-tts')),
                'voice_profile' => (string) ($options['voice_profile'] ?? ''),
                'latency_ms' => $durationMs,
                'usage' => $this->resolveUsageMetadata($response->json()),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    private function request(array $payload, array $options): Response
    {
        $request = Http::connectTimeout($this->connectTimeoutSeconds())
            ->timeout($this->requestTimeoutSeconds());

        $bearerToken = trim((string) ($options['bearer_token'] ?? config('services.speech.gemini_live_native_audio.bearer_token', '')));
        $apiKey = trim((string) ($options['api_key'] ?? config('services.speech.gemini_live_native_audio.api_key', config('services.gemini.key', ''))));
        $endpoint = trim((string) ($options['endpoint'] ?? config('services.speech.gemini_live_native_audio.endpoint', '')));

        if ($endpoint === '') {
            throw new RuntimeException('Gemini native audio endpoint is not configured.');
        }

        if ($bearerToken !== '') {
            $request = $request->withToken($bearerToken);
        }

        if ($apiKey !== '' && str_contains($endpoint, 'key=') === false) {
            $separator = str_contains($endpoint, '?') ? '&' : '?';
            $endpoint .= "{$separator}key={$apiKey}";
        }

        try {
            return $request->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Gemini native audio request timed out or could not connect: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function payload(string $text, array $options): array
    {
        $styleInstruction = trim((string) ($options['style_instruction'] ?? ''));
        $prompt = $styleInstruction !== ''
            ? trim($styleInstruction)."\n\nSkript:\n".$text
            : $text;
        $voiceName = trim((string) ($options['voice'] ?? ''));

        return [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['AUDIO'],
                'temperature' => (float) config('services.speech.gemini_live_native_audio.temperature', 0.6),
                'speechConfig' => [
                    'voiceConfig' => [
                        'prebuiltVoiceConfig' => [
                            'voiceName' => $voiceName !== '' ? $voiceName : 'Kore',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $responseJson
     */
    private function resolveAudioBase64(?array $responseJson): string
    {
        if (! is_array($responseJson)) {
            return '';
        }

        $candidates = $responseJson['candidates'] ?? null;

        if (! is_array($candidates) || ! is_array($candidates[0] ?? null)) {
            return '';
        }

        $parts = data_get($candidates[0], 'content.parts', []);

        if (! is_array($parts)) {
            return '';
        }

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }

            $base64 = (string) (
                data_get($part, 'inlineData.data')
                ?? data_get($part, 'inline_data.data')
                ?? data_get($part, 'audio.data')
                ?? ''
            );

            if ($base64 !== '') {
                return $base64;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>|null  $responseJson
     */
    private function resolveMimeType(?array $responseJson, string $default): string
    {
        if (! is_array($responseJson)) {
            return $default;
        }

        $candidates = $responseJson['candidates'] ?? null;

        if (! is_array($candidates) || ! is_array($candidates[0] ?? null)) {
            return $default;
        }

        $parts = data_get($candidates[0], 'content.parts', []);

        if (! is_array($parts)) {
            return $default;
        }

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }

            $mime = (string) (
                data_get($part, 'inlineData.mimeType')
                ?? data_get($part, 'inline_data.mime_type')
                ?? data_get($part, 'audio.mimeType')
                ?? ''
            );

            if ($mime !== '') {
                return $mime;
            }
        }

        return $default;
    }

    /**
     * @param  array<string, mixed>|null  $responseJson
     * @return array<string, int|float|string>
     */
    private function resolveUsageMetadata(?array $responseJson): array
    {
        if (! is_array($responseJson)) {
            return [];
        }

        $usage = (array) ($responseJson['usageMetadata'] ?? []);

        return array_filter([
            'prompt_token_count' => isset($usage['promptTokenCount']) ? (int) $usage['promptTokenCount'] : null,
            'candidates_token_count' => isset($usage['candidatesTokenCount']) ? (int) $usage['candidatesTokenCount'] : null,
            'total_token_count' => isset($usage['totalTokenCount']) ? (int) $usage['totalTokenCount'] : null,
            'audio_duration_seconds' => isset($usage['audioDurationSeconds']) ? (float) $usage['audioDurationSeconds'] : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function extensionForMime(string $mimeType): string
    {
        $normalized = strtolower(trim($mimeType));

        if ($this->isLinearPcmMime($normalized)) {
            return 'wav';
        }

        return match ($normalized) {
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/mpeg', 'audio/mp3' => 'mp3',
            default => throw new RuntimeException("Unsupported Gemini native audio mime type [{$mimeType}]."),
        };
    }

    private function isLinearPcmMime(string $mimeType): bool
    {
        return str_starts_with(strtolower(trim($mimeType)), 'audio/l16');
    }

    private function sampleRateFromMime(string $mimeType): int
    {
        if (preg_match('/rate\s*=\s*(\d+)/i', $mimeType, $matches) === 1) {
            return max(8000, (int) $matches[1]);
        }

        return 24000;
    }

    private function wrapPcm16ToWav(string $pcm, int $sampleRate): string
    {
        $channels = 1;
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

    private function connectTimeoutSeconds(): int
    {
        return (int) config('services.speech.gemini_live_native_audio.connect_timeout_seconds', 10);
    }

    private function requestTimeoutSeconds(): int
    {
        return (int) config('services.speech.gemini_live_native_audio.request_timeout_seconds', 60);
    }
}
