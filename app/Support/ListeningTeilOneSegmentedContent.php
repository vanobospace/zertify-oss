<?php

namespace App\Support;

class ListeningTeilOneSegmentedContent
{
    public const FORMAT = 'listening_segmented_true_false';

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function normalize(array $content): array
    {
        $intro = is_array($content['intro'] ?? null) ? $content['intro'] : [];
        $segments = is_array($content['segments'] ?? null) ? array_values(array_filter($content['segments'], 'is_array')) : [];
        $audio = is_array($content['audio'] ?? null) ? $content['audio'] : [];

        $normalizedIntro = [
            'text' => self::stringValue($intro['text'] ?? 'Guten Tag. Sie hören jetzt fünf Meldungen aus den Regionen.'),
            'voice_profile' => self::stringValue($intro['voice_profile'] ?? config('services.speech.real_teil1.default_intro_voice_profile', 'anchor_main')),
        ];

        $normalizedSegments = [];
        $statements = [];
        $correct = [];
        $explanations = [];

        foreach ($segments as $index => $segment) {
            $position = $index + 1;
            $statementId = self::stringValue($segment['statement_id'] ?? "statement_{$position}");
            $segmentId = self::stringValue($segment['id'] ?? "segment_{$position}");
            $segmentVoiceProfiles = config('services.speech.real_teil1.segment_voice_cycle', ['news_main']);
            $defaultVoiceProfile = config('services.speech.real_teil1.default_segment_voice_profile')
                ?? ($segmentVoiceProfiles[$index % max(count($segmentVoiceProfiles), 1)] ?? 'news_main');

            $normalizedSegments[] = [
                'id' => $segmentId,
                'number' => $position,
                'voice_profile' => self::stringValue($segment['voice_profile'] ?? $defaultVoiceProfile),
                'segment_text' => self::stringValue($segment['segment_text'] ?? ''),
                'statement_id' => $statementId,
                'statement_text' => self::stringValue($segment['statement_text'] ?? ''),
                'correct_answer' => self::normalizeBoolAnswer($segment['correct_answer'] ?? ''),
                'reason' => self::stringValue($segment['reason'] ?? ''),
                'evidence' => self::stringValue($segment['evidence'] ?? ''),
                'wrong_answer_reason' => self::stringValue($segment['wrong_answer_reason'] ?? ''),
                'strategy_hint' => self::stringValue($segment['strategy_hint'] ?? ''),
            ];

            $statements[] = [
                'id' => $statementId,
                'number' => $position,
                'text' => self::stringValue($segment['statement_text'] ?? ''),
            ];
            $correct[$statementId] = self::normalizeBoolAnswer($segment['correct_answer'] ?? '');
            $explanations[$statementId] = [
                'correct_answer' => self::normalizeBoolAnswer($segment['correct_answer'] ?? ''),
                'reason' => self::stringValue($segment['reason'] ?? ''),
                'evidence' => self::stringValue($segment['evidence'] ?? ''),
                'wrong_answer_reason' => self::stringValue($segment['wrong_answer_reason'] ?? ''),
                'strategy_hint' => self::stringValue($segment['strategy_hint'] ?? ''),
            ];
        }

        $transcriptParts = array_values(array_filter([
            $normalizedIntro['text'],
            ...array_map(static fn (array $segment): string => $segment['segment_text'], $normalizedSegments),
        ], static fn (?string $item): bool => is_string($item) && trim($item) !== ''));

        return [
            ...$content,
            'format' => self::FORMAT,
            'audio' => [
                ...$audio,
                'title' => self::stringValue($audio['title'] ?? ''),
                'audio_notes' => self::stringValue($audio['audio_notes'] ?? ''),
            ],
            'intro' => $normalizedIntro,
            'segments' => $normalizedSegments,
            'transcript' => implode("\n\n", $transcriptParts),
            'statements' => $statements,
            'correct' => $correct,
            'explanation' => $explanations,
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function toStructured(array $content): array
    {
        $normalized = self::normalize($content);
        $audio = is_array($normalized['audio'] ?? null) ? $normalized['audio'] : [];
        $intro = is_array($normalized['intro'] ?? null) ? $normalized['intro'] : [];

        return [
            'instructions' => self::stringValue($normalized['instructions'] ?? null),
            'audio_title' => self::stringValue($audio['title'] ?? null),
            'audio_notes' => self::stringValue($audio['audio_notes'] ?? null),
            'intro_text' => self::stringValue($intro['text'] ?? null),
            'intro_voice_profile' => self::stringValue($intro['voice_profile'] ?? null),
            'transcript' => self::stringValue($normalized['transcript'] ?? null),
            'segments' => array_map(
                static fn (array $segment): array => [
                    'id' => self::stringValue($segment['id'] ?? null),
                    'number' => (int) ($segment['number'] ?? 1),
                    'voice_profile' => self::stringValue($segment['voice_profile'] ?? null),
                    'segment_text' => self::stringValue($segment['segment_text'] ?? null),
                    'statement_id' => self::stringValue($segment['statement_id'] ?? null),
                    'statement_text' => self::stringValue($segment['statement_text'] ?? null),
                    'correct_answer' => self::normalizeBoolAnswer($segment['correct_answer'] ?? null),
                    'reason' => self::stringValue($segment['reason'] ?? null),
                    'evidence' => self::stringValue($segment['evidence'] ?? null),
                    'wrong_answer_reason' => self::stringValue($segment['wrong_answer_reason'] ?? null),
                    'strategy_hint' => self::stringValue($segment['strategy_hint'] ?? null),
                ],
                $normalized['segments'],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $existingContent
     * @param  array<string, mixed>  $structured
     * @return array<string, mixed>
     */
    public static function mergeStructured(array $existingContent, array $structured): array
    {
        $content = [
            ...$existingContent,
            'format' => self::FORMAT,
            'instructions' => self::stringValue($structured['instructions'] ?? null),
            'audio' => array_merge(
                is_array($existingContent['audio'] ?? null) ? $existingContent['audio'] : [],
                [
                    'title' => self::stringValue($structured['audio_title'] ?? null),
                    'audio_notes' => self::stringValue($structured['audio_notes'] ?? null),
                ],
            ),
            'intro' => [
                'text' => self::stringValue($structured['intro_text'] ?? null),
                'voice_profile' => self::stringValue($structured['intro_voice_profile'] ?? null),
            ],
            'segments' => array_map(
                static fn (array $segment): array => [
                    'id' => self::stringValue($segment['id'] ?? null),
                    'number' => max(1, (int) ($segment['number'] ?? 1)),
                    'voice_profile' => self::stringValue($segment['voice_profile'] ?? null),
                    'segment_text' => self::stringValue($segment['segment_text'] ?? null),
                    'statement_id' => self::stringValue($segment['statement_id'] ?? null),
                    'statement_text' => self::stringValue($segment['statement_text'] ?? null),
                    'correct_answer' => self::normalizeBoolAnswer($segment['correct_answer'] ?? null),
                    'reason' => self::stringValue($segment['reason'] ?? null),
                    'evidence' => self::stringValue($segment['evidence'] ?? null),
                    'wrong_answer_reason' => self::stringValue($segment['wrong_answer_reason'] ?? null),
                    'strategy_hint' => self::stringValue($segment['strategy_hint'] ?? null),
                ],
                is_array($structured['segments'] ?? null) ? array_values(array_filter($structured['segments'], 'is_array')) : [],
            ),
        ];

        return self::normalize($content);
    }

    /**
     * @param  array<string, array<string, string>>  $explanations
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function applyExplanations(array $content, array $explanations): array
    {
        $normalized = self::normalize($content);
        $segments = array_map(static function (array $segment) use ($explanations): array {
            $statementId = (string) ($segment['statement_id'] ?? '');
            $details = $explanations[$statementId] ?? null;

            if (! is_array($details)) {
                return $segment;
            }

            $segment['correct_answer'] = self::normalizeBoolAnswer($details['correct_answer'] ?? $segment['correct_answer'] ?? '');
            $segment['reason'] = self::stringValue($details['reason'] ?? $segment['reason'] ?? '');
            $segment['evidence'] = self::stringValue($details['evidence'] ?? $segment['evidence'] ?? '');

            return $segment;
        }, $normalized['segments']);

        $normalized['segments'] = $segments;

        return self::normalize($normalized);
    }

    private static function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private static function normalizeBoolAnswer(mixed $value): string
    {
        return strtolower(self::stringValue($value)) === 'false' ? 'false' : 'true';
    }
}
