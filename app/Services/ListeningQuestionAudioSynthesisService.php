<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Support\ListeningTeilOneSegmentedContent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ListeningQuestionAudioSynthesisService
{
    public function __construct(
        private SpeechSynthesisManager $speechSynthesisManager,
        private ListeningAudioPostProcessor $listeningAudioPostProcessor,
        private ListeningTeilOneSegmentedAudioAssemblyService $listeningTeilOneSegmentedAudioAssemblyService,
        private ListeningTeilTwoDialogueAudioAssemblyService $listeningTeilTwoDialogueAudioAssemblyService,
    ) {}

    public function synthesizeForQuestion(Question $question): QuestionAudioAsset
    {
        $format = $question->resolveFormat();

        $content = is_array($question->content) ? $question->content : [];
        $resolvedSynthesisOptions = $this->speechSynthesisManager->resolveOptions([
            'voice_preset' => $this->resolveVoicePreset($question),
            'output_format' => (string) config('services.speech.default_output_format', 'wav'),
            'module_slug' => (string) ($question->module?->slug ?? ''),
        ]);
        $generatedAudio = $this->synthesizeAudio($content, $format, $question, $resolvedSynthesisOptions);
        $disk = (string) config('services.speech.storage_disk', 'public');
        $filename = $this->buildFilename($question, $generatedAudio['extension']);
        $path = 'question-audio/generated/'.$filename;
        $transcriptHash = $question->currentListeningTranscriptHash();
        $stylePreset = $this->resolveAudioStylePreset($question);

        Storage::disk($disk)->put($path, $generatedAudio['binary']);

        $title = trim((string) ($content['audio']['title'] ?? $question->topic ?? 'Listening audio'));

        $asset = QuestionAudioAsset::query()->create([
            'label' => 'AI spike: '.$title,
            'path' => $path,
            'disk' => $disk,
            'original_name' => $filename,
            'transcript_hash' => $transcriptHash,
            'generation_metadata' => $this->buildGenerationMetadata($question, $resolvedSynthesisOptions, $stylePreset, $transcriptHash, $generatedAudio),
            'generated_at' => Carbon::now(),
            'duration_seconds' => null,
            'is_active' => true,
        ]);

        $audio = is_array($content['audio'] ?? null) ? $content['audio'] : [];
        $audio['url'] = $asset->public_url;
        $content['audio'] = $audio;

        $question->forceFill([
            'question_audio_asset_id' => $asset->id,
            'audio_source_type' => Question::AUDIO_SOURCE_ASSET,
            'audio_external_url' => null,
            'content' => $content,
        ])->save();

        return $asset;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $resolvedSynthesisOptions
     * @return array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}
     */
    private function synthesizeAudio(array $content, string $format, Question $question, array $resolvedSynthesisOptions): array
    {
        if ($format === ListeningTeilOneSegmentedContent::FORMAT) {
            return $this->synthesizeSegmentedTeilOneAudio($content, $question, $resolvedSynthesisOptions);
        }

        if (! in_array($format, ['listening_short_true_false', 'listening_long_true_false'], true)) {
            throw new RuntimeException('Audio synthesis spike currently supports only listening_short_true_false and listening_segmented_true_false.');
        }

        $transcript = trim((string) ($content['transcript'] ?? ''));

        if ($transcript === '') {
            throw new RuntimeException('This listening question does not have a transcript yet.');
        }

        if (
            $format === 'listening_long_true_false'
            && $this->listeningTeilTwoDialogueAudioAssemblyService->hasSpeakerTurns($transcript)
        ) {
            $audio = $this->listeningTeilTwoDialogueAudioAssemblyService->synthesize($content, $resolvedSynthesisOptions);

            return $this->listeningAudioPostProcessor->process(
                $audio,
                $this->resolveAudioStylePreset($question),
            );
        }

        $audio = $this->speechSynthesisManager->synthesize($transcript, $resolvedSynthesisOptions);

        return $this->listeningAudioPostProcessor->process(
            $audio,
            $this->resolveAudioStylePreset($question),
        );
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $resolvedSynthesisOptions
     * @return array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}
     */
    private function synthesizeSegmentedTeilOneAudio(array $content, Question $question, array $resolvedSynthesisOptions): array
    {
        $normalized = ListeningTeilOneSegmentedContent::normalize($content);
        $transcript = trim((string) ($normalized['transcript'] ?? ''));

        if ($transcript === '') {
            throw new RuntimeException('This listening question does not have a transcript yet.');
        }

        if ($this->usesNativeAudioForTeilOne($resolvedSynthesisOptions, $question)) {
            $this->logTeilOneSoftCostWarning($transcript, $question, $resolvedSynthesisOptions);
            try {
                $audio = $this->listeningTeilOneSegmentedAudioAssemblyService->synthesize($normalized, $resolvedSynthesisOptions);

                $audio = $this->listeningAudioPostProcessor->process(
                    $audio,
                    $this->resolveAudioStylePreset($question),
                );

                return $this->applyTeilOneCanonEffects($audio, $question);
            } catch (RuntimeException $exception) {
                if (! $this->isGeminiQuotaError($exception)) {
                    throw $exception;
                }

                Log::warning('Gemini native audio quota exceeded for Hören Teil 1; falling back to Google TTS.', [
                    'question_id' => $question->getKey(),
                    'module_slug' => (string) ($question->module?->slug ?? ''),
                    'provider' => (string) ($resolvedSynthesisOptions['provider'] ?? ''),
                    'message' => $exception->getMessage(),
                ]);

                $fallbackAudio = $this->synthesizeTeilOneFallbackWithGoogle($normalized, $resolvedSynthesisOptions, $question);
                $fallbackMetadata = is_array($fallbackAudio['metadata'] ?? null) ? $fallbackAudio['metadata'] : [];
                $fallbackAudio['metadata'] = [
                    ...$fallbackMetadata,
                    'fallback_from_provider' => 'gemini_live_native_audio',
                    'fallback_reason' => 'gemini_quota_exceeded_429',
                ];

                return $this->applyTeilOneCanonEffects($fallbackAudio, $question);
            }
        }

        $intro = is_array($normalized['intro'] ?? null) ? $normalized['intro'] : [];
        $audio = $this->speechSynthesisManager->synthesize($transcript, [
            ...$resolvedSynthesisOptions,
            'voice_profile' => (string) ($intro['voice_profile'] ?? config('services.speech.real_teil1.default_intro_voice_profile', 'anchor_main')),
        ]);

        $audio = $this->listeningAudioPostProcessor->process(
            $audio,
            $this->resolveAudioStylePreset($question),
        );

        return $this->applyTeilOneCanonEffects($audio, $question);
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $resolvedSynthesisOptions
     * @return array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}
     */
    private function synthesizeTeilOneFallbackWithGoogle(array $normalized, array $resolvedSynthesisOptions, Question $question): array
    {
        $intro = is_array($normalized['intro'] ?? null) ? $normalized['intro'] : [];
        $transcript = trim((string) ($normalized['transcript'] ?? ''));

        $audio = $this->speechSynthesisManager->synthesize($transcript, [
            ...$resolvedSynthesisOptions,
            'provider' => 'google_cloud_tts',
            'voice_profile' => (string) ($intro['voice_profile'] ?? config('services.speech.real_teil1.default_intro_voice_profile', 'anchor_main')),
            'output_format' => 'wav',
        ]);

        return $this->listeningAudioPostProcessor->process(
            $audio,
            $this->resolveAudioStylePreset($question),
        );
    }

    private function isGeminiQuotaError(RuntimeException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'status code 429')
            || str_contains($message, 'quota')
            || str_contains($message, 'rate limit');
    }

    /**
     * @param  array<string, mixed>  $resolvedSynthesisOptions
     */
    private function usesNativeAudioForTeilOne(array $resolvedSynthesisOptions, Question $question): bool
    {
        return ($resolvedSynthesisOptions['provider'] ?? null) === 'gemini_live_native_audio'
            && ($question->module?->slug ?? null) === 'hoeren-teil-1';
    }

    /**
     * @param  array<string, mixed>  $resolvedSynthesisOptions
     */
    private function logTeilOneSoftCostWarning(string $transcript, Question $question, array $resolvedSynthesisOptions): void
    {
        $estimatedPromptTokens = max(1, (int) ceil(mb_strlen($transcript) / 4));
        $softCapPromptTokens = (int) config('services.speech.gemini_live_native_audio.soft_cap_prompt_tokens', 3500);

        if ($estimatedPromptTokens <= $softCapPromptTokens) {
            return;
        }

        Log::warning('Gemini native audio soft cost cap exceeded for Hören Teil 1 generation.', [
            'question_id' => $question->getKey(),
            'module_slug' => (string) ($question->module?->slug ?? ''),
            'provider' => (string) ($resolvedSynthesisOptions['provider'] ?? ''),
            'model' => (string) ($resolvedSynthesisOptions['model'] ?? ''),
            'estimated_prompt_tokens' => $estimatedPromptTokens,
            'soft_cap_prompt_tokens' => $softCapPromptTokens,
            'cost_cap_soft_warn' => true,
        ]);
    }

    private function resolveVoicePreset(Question $question): string
    {
        $preset = trim((string) ($question->audio_voice_preset ?? ''));

        return $preset !== '' ? $preset : Question::AUDIO_VOICE_PRESET_NEWS_FEMALE;
    }

    private function resolveAudioStylePreset(Question $question): string
    {
        $preset = trim((string) ($question->audio_style_preset ?? ''));

        return $preset !== '' ? $preset : Question::AUDIO_STYLE_PRESET_CLEAN;
    }

    private function buildFilename(Question $question, string $extension): string
    {
        $slug = Str::slug((string) ($question->topic ?: 'listening-question'));
        $slug = $slug !== '' ? $slug : 'listening-question';

        return $slug.'-'.$question->getKey().'-'.Str::lower(Str::random(8)).'.'.$extension;
    }

    /**
     * @param  array<string, mixed>  $resolvedSynthesisOptions
     * @param  array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}  $generatedAudio
     * @return array<string, mixed>
     */
    private function buildGenerationMetadata(Question $question, array $resolvedSynthesisOptions, string $stylePreset, ?string $transcriptHash, array $generatedAudio): array
    {
        $audioMetadata = is_array($generatedAudio['metadata'] ?? null) ? $generatedAudio['metadata'] : [];
        $usageMetadata = is_array($audioMetadata['usage'] ?? null) ? $audioMetadata['usage'] : [];
        $model = (string) ($audioMetadata['model'] ?? ($resolvedSynthesisOptions['model'] ?? ''));
        $provider = (string) ($audioMetadata['provider'] ?? ($resolvedSynthesisOptions['provider'] ?? 'google_cloud_tts'));
        $latencyMs = (int) ($audioMetadata['latency_ms'] ?? 0);
        $costCapSoftWarn = false;

        if ($provider === 'gemini_live_native_audio' && ($question->module?->slug ?? null) === 'hoeren-teil-1') {
            $transcript = trim((string) ($question->resolveListeningTranscriptForAudio() ?? ''));

            if ($transcript !== '') {
                $estimatedPromptTokens = max(1, (int) ceil(mb_strlen($transcript) / 4));
                $softCapPromptTokens = (int) config('services.speech.gemini_live_native_audio.soft_cap_prompt_tokens', 3500);
                $costCapSoftWarn = $estimatedPromptTokens > $softCapPromptTokens;
            }
        }

        return [
            'provider' => $provider,
            'model' => $model,
            'voice' => (string) ($resolvedSynthesisOptions['voice'] ?? ''),
            'voice_preset' => $this->resolveVoicePreset($question),
            'dialogue_pair_preset' => (string) ($audioMetadata['dialogue_pair_preset'] ?? ''),
            'dialogue_pair' => is_array($audioMetadata['dialogue_pair'] ?? null) ? $audioMetadata['dialogue_pair'] : [],
            'clips' => is_array($audioMetadata['clips'] ?? null) ? $audioMetadata['clips'] : [],
            'voice_profile' => (string) ($audioMetadata['voice_profile'] ?? ($resolvedSynthesisOptions['voice_profile'] ?? '')),
            'language_code' => (string) ($resolvedSynthesisOptions['language_code'] ?? ''),
            'speaking_rate' => (float) ($resolvedSynthesisOptions['speaking_rate'] ?? 1.0),
            'pitch' => (float) ($resolvedSynthesisOptions['pitch'] ?? 0.0),
            'output_format' => (string) ($resolvedSynthesisOptions['output_format'] ?? 'wav'),
            'style_preset' => $stylePreset,
            'question_format' => (string) ($question->resolveFormat() ?? ''),
            'transcript_hash' => $transcriptHash,
            'latency_ms' => $latencyMs,
            'usage' => $usageMetadata,
            'cost_cap_soft_warn' => $costCapSoftWarn,
            'fallback_from_provider' => (string) ($audioMetadata['fallback_from_provider'] ?? ''),
            'fallback_reason' => (string) ($audioMetadata['fallback_reason'] ?? ''),
            'effects_profile' => is_array($audioMetadata['effects_profile'] ?? null) ? $audioMetadata['effects_profile'] : [
                'profile' => 'none',
                'enabled' => false,
                'applied' => false,
            ],
        ];
    }

    /**
     * @param  array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}  $audio
     * @return array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}
     */
    private function applyTeilOneCanonEffects(array $audio, Question $question): array
    {
        if (($question->module?->slug ?? null) !== 'hoeren-teil-1') {
            return $audio;
        }

        $result = $this->listeningAudioPostProcessor->processTeilOneCanonEffects($audio);
        $processedAudio = $result['audio'];
        $effectsMetadata = is_array($result['effects'] ?? null) ? $result['effects'] : [
            'profile' => 'hoeren_teil1_canon',
            'enabled' => false,
            'applied' => false,
        ];
        $existingMetadata = is_array($processedAudio['metadata'] ?? null) ? $processedAudio['metadata'] : [];
        $processedAudio['metadata'] = [
            ...$existingMetadata,
            'effects_profile' => $effectsMetadata,
        ];

        return $processedAudio;
    }
}
