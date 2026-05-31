<?php

namespace App\Services;

use RuntimeException;

class SpeechSynthesisManager
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{binary: string, extension: string, mime_type: string}
     */
    public function synthesize(string $text, array $options = []): array
    {
        $resolvedOptions = $this->resolveOptions($options);
        $provider = $this->providerFor((string) ($resolvedOptions['provider'] ?? 'google_cloud_tts'));
        $audio = $provider->synthesize($text, $resolvedOptions);
        $metadata = is_array($audio['metadata'] ?? null) ? $audio['metadata'] : [];

        return [
            ...$audio,
            'metadata' => [
                ...$metadata,
                'provider' => (string) ($metadata['provider'] ?? ($resolvedOptions['provider'] ?? 'google_cloud_tts')),
                'model' => (string) ($metadata['model'] ?? ($resolvedOptions['model'] ?? '')),
                'voice_profile' => (string) ($resolvedOptions['voice_profile'] ?? ''),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function resolveOptions(array $options = []): array
    {
        $provider = $this->resolveProviderKey($options);

        if ($provider === 'gemini_live_native_audio') {
            $resolvedOptions = $this->resolveVoiceProfileOptions([
                ...$options,
                'provider' => $provider,
            ]);
            $resolvedOptions = $this->resolveVoicePresetOptions($resolvedOptions);

            return [
                ...$resolvedOptions,
                'provider' => $provider,
                'model' => (string) ($resolvedOptions['model'] ?? config('services.speech.gemini_live_native_audio.model', 'gemini-2.5-flash-preview-tts')),
                'endpoint' => (string) ($resolvedOptions['endpoint'] ?? config('services.speech.gemini_live_native_audio.endpoint', '')),
                'mime_type' => (string) ($resolvedOptions['mime_type'] ?? config('services.speech.gemini_live_native_audio.mime_type', 'audio/wav')),
                'output_format' => strtolower((string) ($resolvedOptions['output_format'] ?? 'wav')),
            ];
        }

        $resolvedOptions = $this->resolveVoiceProfileOptions($options);
        $resolvedOptions = $this->resolveVoicePresetOptions($resolvedOptions);

        return [
            ...$resolvedOptions,
            'provider' => 'google_cloud_tts',
            'language_code' => (string) config('services.speech.google_cloud_tts.language_code', 'de-DE'),
            'speaking_rate' => (float) config('services.speech.google_cloud_tts.speaking_rate', 1.0),
            'pitch' => (float) config('services.speech.google_cloud_tts.pitch', 0.0),
            'output_format' => strtolower((string) ($resolvedOptions['output_format'] ?? config('services.speech.default_output_format', 'wav'))),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function resolveVoiceProfileOptions(array $options): array
    {
        $voiceProfile = $options['voice_profile'] ?? null;

        if (! is_string($voiceProfile) || $voiceProfile === '') {
            return $options;
        }

        $provider = (string) ($options['provider'] ?? $this->resolveProviderKey($options));
        $profile = $provider === 'gemini_live_native_audio'
            ? config("services.speech.gemini_live_native_audio.voice_profiles.{$voiceProfile}")
            : config("services.speech.real_teil1.voice_profiles.{$voiceProfile}");

        if (! is_array($profile)) {
            throw new RuntimeException("Unknown speech voice profile [{$voiceProfile}].");
        }

        return [
            ...$profile,
            ...$options,
        ];
    }

    private function resolveProviderKey(array $options): string
    {
        $explicitProvider = trim((string) ($options['provider'] ?? ''));

        if ($explicitProvider !== '') {
            return $explicitProvider;
        }

        $moduleSlug = trim((string) ($options['module_slug'] ?? ''));
        $nativeForTeilOneEnabled = (bool) config('services.speech.gemini_live_native_audio.enabled_for_hoeren_teil1', false);

        if ($nativeForTeilOneEnabled && $moduleSlug === 'hoeren-teil-1') {
            return 'gemini_live_native_audio';
        }

        return 'google_cloud_tts';
    }

    private function providerFor(string $provider): SpeechSynthesisProvider
    {
        return match ($provider) {
            'gemini_live_native_audio' => app(GeminiLiveNativeAudioService::class),
            default => app(GoogleCloudTextToSpeechService::class),
        };
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function resolveVoicePresetOptions(array $options): array
    {
        $voicePreset = $options['voice_preset'] ?? null;

        if (! is_string($voicePreset) || $voicePreset === '') {
            return $options;
        }

        $provider = (string) ($options['provider'] ?? $this->resolveProviderKey($options));
        $providerVoiceConfig = config("services.speech.voice_presets.{$provider}.{$voicePreset}");

        if (is_array($providerVoiceConfig)) {
            $voice = trim((string) ($providerVoiceConfig['voice'] ?? ''));
            $speakingRate = $providerVoiceConfig['speaking_rate'] ?? null;
            $pitch = $providerVoiceConfig['pitch'] ?? null;

            $resolved = $options;

            if ($voice !== '') {
                $resolved['voice'] = $voice;
            }

            if (is_numeric($speakingRate)) {
                $resolved['speaking_rate'] = (float) $speakingRate;
            }

            if (is_numeric($pitch)) {
                $resolved['pitch'] = (float) $pitch;
            }

            return $resolved;
        }

        if (! is_string($providerVoiceConfig) || $providerVoiceConfig === '') {
            return $options;
        }

        return [
            ...$options,
            'voice' => $providerVoiceConfig,
        ];
    }
}
