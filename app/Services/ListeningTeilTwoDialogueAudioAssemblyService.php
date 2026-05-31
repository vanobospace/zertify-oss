<?php

namespace App\Services;

use App\Models\Question;
use RuntimeException;

class ListeningTeilTwoDialogueAudioAssemblyService
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
        $transcript = trim((string) ($content['transcript'] ?? ''));

        if ($transcript === '') {
            throw new RuntimeException('This listening question does not have a transcript yet.');
        }

        $speakerTurns = $this->extractSpeakerTurns($transcript);

        if ($speakerTurns === []) {
            throw new RuntimeException('Transcript does not contain speaker turns for Teil 2 dialogue assembly.');
        }

        $pairPreset = $this->resolveDialoguePairPreset((string) ($baseOptions['voice_preset'] ?? ''));
        $pair = $this->resolvePairVoices($pairPreset);
        $clips = [];
        $clipMetadata = [];

        foreach ($speakerTurns as $turn) {
            $voicePreset = $turn['role'] === 'guest'
                ? $pair['guest_voice_preset']
                : $pair['interviewer_voice_preset'];
            $clip = $this->speechSynthesisManager->synthesize($turn['text'], [
                ...$baseOptions,
                'voice_preset' => $voicePreset,
                'output_format' => (string) ($baseOptions['output_format'] ?? 'wav'),
            ]);

            if (($clip['extension'] ?? null) !== 'wav') {
                throw new RuntimeException('Teil 2 dialogue assembly requires wav output from the active speech provider.');
            }

            $clips[] = (string) $clip['binary'];
            $clipMetadata[] = [
                ...(is_array($clip['metadata'] ?? null) ? $clip['metadata'] : []),
                'role' => $turn['role'],
                'speaker_label' => $turn['speaker'],
                'voice_preset' => $voicePreset,
            ];
        }

        return [
            'binary' => $this->waveAudioAssembler->concatenate(
                $clips,
                (int) config('services.speech.hoeren_teil2_dialogue.pause_ms', 420),
            ),
            'extension' => 'wav',
            'mime_type' => 'audio/wav',
            'metadata' => [
                'provider' => (string) ($clipMetadata[0]['provider'] ?? ''),
                'model' => (string) ($clipMetadata[0]['model'] ?? ''),
                'dialogue_pair_preset' => $pairPreset,
                'dialogue_pair' => $pair,
                'clips' => $clipMetadata,
                'usage' => $this->aggregateUsage($clipMetadata),
            ],
        ];
    }

    public function hasSpeakerTurns(string $transcript): bool
    {
        return $this->extractSpeakerTurns($transcript) !== [];
    }

    private function resolveDialoguePairPreset(string $preset): string
    {
        return match (trim($preset)) {
            Question::AUDIO_VOICE_PRESET_DIALOG_MF,
            Question::AUDIO_VOICE_PRESET_DIALOG_FM,
            Question::AUDIO_VOICE_PRESET_DIALOG_MM,
            Question::AUDIO_VOICE_PRESET_DIALOG_FF => trim($preset),
            Question::AUDIO_VOICE_PRESET_NEWS_MALE,
            Question::AUDIO_VOICE_PRESET_NEUTRAL_MALE,
            Question::AUDIO_VOICE_PRESET_ANCHOR_MALE,
            Question::AUDIO_VOICE_PRESET_REPORTER_MALE => Question::AUDIO_VOICE_PRESET_DIALOG_MM,
            Question::AUDIO_VOICE_PRESET_NEUTRAL_FEMALE,
            Question::AUDIO_VOICE_PRESET_ANCHOR_FEMALE,
            Question::AUDIO_VOICE_PRESET_REPORTER_FEMALE,
            Question::AUDIO_VOICE_PRESET_NEWS_FEMALE => Question::AUDIO_VOICE_PRESET_DIALOG_MF,
            default => Question::AUDIO_VOICE_PRESET_DIALOG_MF,
        };
    }

    /**
     * @return array{interviewer_voice_preset: string, guest_voice_preset: string}
     */
    private function resolvePairVoices(string $pairPreset): array
    {
        $configured = config("services.speech.dialogue_voice_pairs.{$pairPreset}");

        if (is_array($configured)) {
            $interviewer = trim((string) ($configured['interviewer_voice_preset'] ?? ''));
            $guest = trim((string) ($configured['guest_voice_preset'] ?? ''));

            if ($interviewer !== '' && $guest !== '') {
                return [
                    'interviewer_voice_preset' => $interviewer,
                    'guest_voice_preset' => $guest,
                ];
            }
        }

        return match ($pairPreset) {
            Question::AUDIO_VOICE_PRESET_DIALOG_FM => [
                'interviewer_voice_preset' => Question::AUDIO_VOICE_PRESET_ANCHOR_FEMALE,
                'guest_voice_preset' => Question::AUDIO_VOICE_PRESET_REPORTER_MALE,
            ],
            Question::AUDIO_VOICE_PRESET_DIALOG_MM => [
                'interviewer_voice_preset' => Question::AUDIO_VOICE_PRESET_ANCHOR_MALE,
                'guest_voice_preset' => Question::AUDIO_VOICE_PRESET_REPORTER_MALE,
            ],
            Question::AUDIO_VOICE_PRESET_DIALOG_FF => [
                'interviewer_voice_preset' => Question::AUDIO_VOICE_PRESET_ANCHOR_FEMALE,
                'guest_voice_preset' => Question::AUDIO_VOICE_PRESET_REPORTER_FEMALE,
            ],
            default => [
                'interviewer_voice_preset' => Question::AUDIO_VOICE_PRESET_ANCHOR_MALE,
                'guest_voice_preset' => Question::AUDIO_VOICE_PRESET_REPORTER_FEMALE,
            ],
        };
    }

    /**
     * @return list<array{speaker: string, text: string, role: string}>
     */
    private function extractSpeakerTurns(string $transcript): array
    {
        $lines = preg_split('/\R/u', $transcript) ?: [];
        $turns = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^([^:]{2,40}):\s*(.+)$/u', $trimmed, $matches) === 1) {
                $speaker = trim((string) ($matches[1] ?? ''));
                $text = trim((string) ($matches[2] ?? ''));

                if ($speaker !== '' && $text !== '') {
                    $turns[] = [
                        'speaker' => $speaker,
                        'text' => $text,
                    ];
                }

                continue;
            }

            $lastIndex = count($turns) - 1;

            if ($lastIndex >= 0) {
                $turns[$lastIndex]['text'] = trim($turns[$lastIndex]['text'].' '.$trimmed);
            }
        }

        if ($turns === []) {
            return [];
        }

        $normalizedTurns = [];

        foreach ($turns as $index => $turn) {
            $role = $this->resolveRoleFromSpeakerLabel((string) ($turn['speaker'] ?? ''), $index);

            $normalizedTurns[] = [
                'speaker' => (string) ($turn['speaker'] ?? ''),
                'text' => (string) ($turn['text'] ?? ''),
                'role' => $role,
            ];
        }

        return $normalizedTurns;
    }

    private function resolveRoleFromSpeakerLabel(string $speakerLabel, int $index): string
    {
        $normalized = $this->normalizeLabel($speakerLabel);

        foreach (['moderator', 'moderatorin', 'interviewer', 'interviewerin', 'journalist', 'journalistin', 'host', 'anchor'] as $token) {
            if (str_contains($normalized, $token)) {
                return 'interviewer';
            }
        }

        foreach (['gast', 'gaestin', 'gastin', 'interviewgast', 'expert', 'expertin', 'sprecherin', 'sprecher'] as $token) {
            if (str_contains($normalized, $token)) {
                return 'guest';
            }
        }

        return $index % 2 === 0 ? 'interviewer' : 'guest';
    }

    private function normalizeLabel(string $value): string
    {
        $lower = mb_strtolower(trim($value));

        return strtr($lower, [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
        ]);
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
