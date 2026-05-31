<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCloudTextToSpeechService implements SpeechSynthesisProvider
{
    public function key(): string
    {
        return 'google_cloud_tts';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{binary: string, extension: string, mime_type: string}
     */
    public function synthesize(string $text, array $options = []): array
    {
        $trimmedText = trim($text);

        if ($trimmedText === '') {
            throw new RuntimeException('Transcript is required for audio synthesis.');
        }

        $outputFormat = strtolower((string) ($options['output_format'] ?? config('services.speech.default_output_format', 'mp3')));
        $audioConfig = $this->audioConfig($outputFormat);

        $voiceName = $this->voiceName($options);
        $response = Http::withToken($this->fetchAccessToken())
            ->timeout(60)
            ->post($this->endpoint(), [
                'input' => [
                    'text' => $trimmedText,
                ],
                'voice' => [
                    'languageCode' => $this->languageCode($options),
                    'name' => $voiceName,
                ],
                'audioConfig' => [
                    'audioEncoding' => $audioConfig['encoding'],
                    'speakingRate' => $this->speakingRate($options),
                    'pitch' => $this->pitch($options),
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Google Cloud TTS request failed: '.$e->getMessage(), previous: $e);
        }

        $audioContent = (string) $response->json('audioContent', '');

        if ($audioContent === '') {
            throw new RuntimeException('Google Cloud TTS returned an empty audio payload.');
        }

        $binary = base64_decode($audioContent, true);

        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('Google Cloud TTS returned invalid base64 audio content.');
        }

        return [
            'binary' => $binary,
            'extension' => $audioConfig['extension'],
            'mime_type' => $audioConfig['mime_type'],
            'metadata' => [
                'provider' => $this->key(),
                'model' => $voiceName,
                'voice' => $voiceName,
            ],
        ];
    }

    /**
     * @return array{encoding: string, extension: string, mime_type: string}
     */
    private function audioConfig(string $outputFormat): array
    {
        return match ($outputFormat) {
            'mp3' => [
                'encoding' => 'MP3',
                'extension' => 'mp3',
                'mime_type' => 'audio/mpeg',
            ],
            'wav', 'linear16' => [
                'encoding' => 'LINEAR16',
                'extension' => 'wav',
                'mime_type' => 'audio/wav',
            ],
            default => throw new RuntimeException("Unsupported Google Cloud TTS output format [{$outputFormat}]."),
        };
    }

    private function fetchAccessToken(): string
    {
        $credentials = $this->serviceAccountCredentials();
        $issuedAt = time();
        $expiresAt = $issuedAt + 3600;

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => $this->tokenUri(),
            'exp' => $expiresAt,
            'iat' => $issuedAt,
        ]));

        $unsignedToken = $header.'.'.$payload;
        $signature = '';
        $privateKey = openssl_pkey_get_private($credentials['private_key']);

        if ($privateKey === false) {
            throw new RuntimeException('Unable to read Google Cloud service account private key.');
        }

        $signed = openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);

        if (! $signed) {
            throw new RuntimeException('Unable to sign Google Cloud service account JWT.');
        }

        $assertion = $unsignedToken.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()
            ->timeout(30)
            ->post($this->tokenUri(), [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Google OAuth token request failed: '.$e->getMessage(), previous: $e);
        }

        $accessToken = (string) $response->json('access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('Google OAuth token response did not contain an access token.');
        }

        return $accessToken;
    }

    /**
     * @return array{client_email: string, private_key: string}
     */
    private function serviceAccountCredentials(): array
    {
        $json = trim((string) config('services.speech.google_cloud_tts.service_account_json', ''));
        $path = trim((string) config('services.speech.google_cloud_tts.service_account_json_path', ''));

        if ($json !== '') {
            $decoded = json_decode($json, true);

            if (is_array($decoded)) {
                return $this->validateCredentialsArray($decoded);
            }
        }

        if ($path !== '' && is_file($path)) {
            $contents = file_get_contents($path);
            $decoded = is_string($contents) ? json_decode($contents, true) : null;

            if (is_array($decoded)) {
                return $this->validateCredentialsArray($decoded);
            }
        }

        throw new RuntimeException('Google Cloud TTS service account credentials are not configured.');
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{client_email: string, private_key: string}
     */
    private function validateCredentialsArray(array $credentials): array
    {
        $clientEmail = trim((string) ($credentials['client_email'] ?? ''));
        $privateKey = trim((string) ($credentials['private_key'] ?? ''));

        if ($clientEmail === '' || $privateKey === '') {
            throw new RuntimeException('Google Cloud TTS credentials must contain client_email and private_key.');
        }

        return [
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
        ];
    }

    private function endpoint(): string
    {
        return (string) config('services.speech.google_cloud_tts.endpoint');
    }

    private function tokenUri(): string
    {
        return (string) config('services.speech.google_cloud_tts.token_uri');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function languageCode(array $options = []): string
    {
        return (string) ($options['language_code'] ?? config('services.speech.google_cloud_tts.language_code', 'de-DE'));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function voiceName(array $options = []): string
    {
        $configuredVoice = (string) config('services.speech.google_cloud_tts.voice_name', 'de-DE-Chirp3-HD-Kore');
        $voice = trim((string) ($options['voice'] ?? ''));

        if ($voice === '') {
            return $configuredVoice;
        }

        return $this->isGoogleVoiceName($voice)
            ? $voice
            : $configuredVoice;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function speakingRate(array $options = []): float
    {
        return (float) ($options['speaking_rate'] ?? config('services.speech.google_cloud_tts.speaking_rate', 1.0));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function pitch(array $options = []): float
    {
        return (float) ($options['pitch'] ?? config('services.speech.google_cloud_tts.pitch', 0.0));
    }

    private function isGoogleVoiceName(string $voice): bool
    {
        return str_starts_with($voice, $this->languageCode().'-');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
