<?php

namespace App\Services;

use App\Support\ListeningTeilOneSegmentedContent;
use RuntimeException;

class ListeningTeilOneSegmentedAudioAssemblyService
{
    public function __construct(
        private SpeechSynthesisManager $speechSynthesisManager,
        private WaveAudioAssembler $waveAudioAssembler,
    ) {}

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $baseOptions
     * @return array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}
     */
    public function synthesize(array $content, array $baseOptions = []): array
    {
        $normalized = ListeningTeilOneSegmentedContent::normalize($content);
        $intro = is_array($normalized['intro'] ?? null) ? $normalized['intro'] : [];
        $segments = is_array($normalized['segments'] ?? null) ? $normalized['segments'] : [];

        $clips = [];
        $clipMetadata = [];
        $introClip = $this->synthesizeClip(
            (string) ($intro['text'] ?? ''),
            (string) ($intro['voice_profile'] ?? 'anchor_main'),
            $baseOptions,
        );
        $clips[] = (string) $introClip['binary'];
        $clipMetadata[] = is_array($introClip['metadata'] ?? null) ? $introClip['metadata'] : [];

        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $clip = $this->synthesizeClip(
                (string) ($segment['segment_text'] ?? ''),
                (string) ($segment['voice_profile'] ?? ''),
                $baseOptions,
            );
            $clips[] = (string) $clip['binary'];
            $clipMetadata[] = is_array($clip['metadata'] ?? null) ? $clip['metadata'] : [];
        }

        if ($clips === []) {
            throw new RuntimeException('No audio clips could be synthesized for segmented Teil 1.');
        }

        return [
            'binary' => $this->waveAudioAssembler->concatenate(
                $clips,
                $this->resolvePauseMilliseconds(),
            ),
            'extension' => 'wav',
            'mime_type' => 'audio/wav',
            'metadata' => [
                'provider' => (string) ($clipMetadata[0]['provider'] ?? ''),
                'model' => (string) ($clipMetadata[0]['model'] ?? ''),
                'clips' => $clipMetadata,
                'usage' => $this->aggregateUsage($clipMetadata),
            ],
        ];
    }

    private function resolvePauseMilliseconds(): int
    {
        $effectsEnabled = (bool) config('services.speech.hoeren_teil1_effects.enabled', true);

        if ($effectsEnabled) {
            return (int) config('services.speech.hoeren_teil1_effects.segment_pause_ms', 520);
        }

        return (int) config('services.speech.real_teil1.pause_milliseconds', 450);
    }

    /**
     * @param  array<string, mixed>  $baseOptions
     * @return array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}
     */
    private function synthesizeClip(string $text, string $voiceProfile, array $baseOptions = []): array
    {
        $trimmedText = trim($text);

        if ($trimmedText === '') {
            throw new RuntimeException('Each segmented Teil 1 clip requires non-empty text.');
        }

        $audio = $this->speechSynthesisManager->synthesize($trimmedText, [
            ...$baseOptions,
            'voice_profile' => $voiceProfile !== '' ? $voiceProfile : ($baseOptions['voice_profile'] ?? ''),
            'output_format' => (string) ($baseOptions['output_format'] ?? 'wav'),
        ]);

        if (($audio['extension'] ?? null) !== 'wav') {
            throw new RuntimeException('Segmented Teil 1 assembly currently requires wav output from the active speech provider.');
        }

        return $audio;
    }

    /**
     * @param  list<array<string, mixed>>  $clips
     * @return array<string, int>
     */
    private function aggregateUsage(array $clips): array
    {
        $totals = [
            'prompt_token_count' => 0,
            'candidates_token_count' => 0,
            'total_token_count' => 0,
        ];

        foreach ($clips as $clip) {
            $usage = is_array($clip['usage'] ?? null) ? $clip['usage'] : [];

            foreach (array_keys($totals) as $key) {
                $totals[$key] += (int) ($usage[$key] ?? 0);
            }
        }

        return $totals;
    }
}
