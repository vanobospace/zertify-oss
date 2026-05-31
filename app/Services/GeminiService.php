<?php

namespace App\Services;

use App\Support\ListeningTeilOneSegmentedContent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    private const MINIMUM_RETRY_WINDOW_SECONDS = 8;

    private string $apiKey;

    private string $model;

    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $this->model = config('services.gemini.model', 'gemini-3.1-flash-lite-preview');
    }

    /**
     * Generate a question draft using Gemini API.
     *
     * @param  array{format: string, difficulty: string, topic_seed?: string, topic_catalog_title?: string, topic_hint?: string, quality_retry_hint?: string, golden_example?: string, module_slug?: string}  $options
     * @return array{topic: string, difficulty: string, content: array<string, mixed>, word_count: int, quality_report?: array<string, mixed>}
     *
     * @throws RuntimeException
     */
    public function generateQuestion(array $options): array
    {
        $this->prepareInteractiveExecutionBudget();

        $format = $options['format'] ?? 'per_gap';
        $validator = app(QuestionGenerationQualityValidator::class);
        $retryHint = trim((string) ($options['quality_retry_hint'] ?? ''));
        $lastErrors = ['Question generation failed before validation.'];
        $startedAt = $this->currentTime();
        $deadline = $startedAt + $this->requestBudgetSeconds();

        for ($attempt = 1; $attempt <= $this->maxGenerationAttempts(); $attempt++) {
            if ($this->shouldStopBeforeRetry($deadline)) {
                throw new RuntimeException(
                    'Question generation exceeded the interactive time budget before another retry could start. '
                    .implode(' | ', $lastErrors)
                );
            }

            try {
                $data = $validator->normalizeGeneratedQuestion(
                    $this->callGemini($this->buildPrompt($options, $retryHint)),
                );
            } catch (RuntimeException $e) {
                $lastErrors = [$e->getMessage()];
                $retryHint = '';

                continue;
            }

            $content = is_array($data['content'] ?? null) ? $data['content'] : [];
            $content = $this->normalizeFormatSpecificContent($content, $format);
            $data['content'] = $content;
            $data['word_count'] = $this->countGeneratedContentWords($content, $format);

            $qualityReport = $validator->validateQuestionContentPayload($content, $format);
            $qualityReport = $this->applyGeneratedDraftRequirements($qualityReport, $content, $format, $options);

            if ($qualityReport['should_regenerate_explanations']) {
                $content = is_array($data['content'] ?? null) ? $data['content'] : [];
                $explanationRetryHint = '';

                for ($explanationAttempt = 1; $explanationAttempt <= 1; $explanationAttempt++) {
                    if ($this->remainingBudgetSeconds($deadline) < $this->explanationRequestTimeoutSeconds()) {
                        if ($this->canBeSoftenedToExplanationReview($qualityReport)) {
                            $data['quality_report'] = $this->softenExplanationFailures($qualityReport);

                            return $data;
                        }

                        break;
                    }

                    $generatedExplanations = $this->generateMissingExplanations(
                        $content,
                        $format,
                        (string) ($data['topic'] ?? ''),
                        $explanationRetryHint,
                    );
                    $content = $this->applyGeneratedExplanations($content, $format, $generatedExplanations);
                    $data['content'] = $content;
                    $data = $validator->normalizeGeneratedQuestion($data);
                    $content = is_array($data['content'] ?? null) ? $data['content'] : [];
                    $content = $this->normalizeFormatSpecificContent($content, $format);
                    $data['content'] = $content;
                    $qualityReport = $validator->validateQuestionContentPayload($content, $format);
                    $qualityReport = $this->applyGeneratedDraftRequirements($qualityReport, $content, $format, $options);
                }

                if ($qualityReport['should_regenerate_explanations']) {
                    if ($this->canBeSoftenedToExplanationReview($qualityReport)) {
                        $data['quality_report'] = $this->softenExplanationFailures($qualityReport);

                        return $data;
                    }
                }
            }

            if ($qualityReport['passed']) {
                $data['quality_report'] = $qualityReport;

                return $data;
            }

            $lastErrors = $qualityReport['errors'];

            if (! $qualityReport['retryable'] && ! $qualityReport['should_regenerate_explanations']) {
                break;
            }

            $retryHint = $validator->buildRetryHint($qualityReport);
        }

        throw new RuntimeException('Question generation failed quality checks: '.implode(' | ', $lastErrors));
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function generateMissingExplanations(array $content, string $format, string $topic = '', string $qualityRetryHint = ''): array
    {
        if (in_array($format, [
            'listening_short_true_false',
            'listening_long_true_false',
            ListeningTeilOneSegmentedContent::FORMAT,
        ], true)) {
            return app(ListeningDraftExplanationGenerationService::class)->generate($content, $topic, $qualityRetryHint);
        }

        return $this->generateExplanations($content, $topic, $qualityRetryHint);
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $explanations
     * @return array<string, mixed>
     */
    private function applyGeneratedExplanations(array $content, string $format, array $explanations): array
    {
        if ($format === ListeningTeilOneSegmentedContent::FORMAT) {
            return ListeningTeilOneSegmentedContent::applyExplanations($content, $explanations);
        }

        $content['explanation'] = $explanations;

        return $content;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizeFormatSpecificContent(array $content, string $format): array
    {
        if ($format === ListeningTeilOneSegmentedContent::FORMAT) {
            return ListeningTeilOneSegmentedContent::normalize($content);
        }

        return $content;
    }

    /**
     * Count words in text, ignoring gap markers like {{gap_N}}.
     */
    public static function countTextWords(string $text): int
    {
        $clean = preg_replace('/\{\{gap_\d+\}\}/', '', $text);

        return preg_match_all('/\p{L}+/u', $clean ?? '');
    }

    /**
     * @return array{topic: string, difficulty: string, content: array<string, mixed>}
     *
     * @throws RuntimeException
     */
    protected function callGemini(string $prompt): array
    {
        $data = $this->callGeminiJson($prompt);

        if (! is_array($data) || empty($data['content'])) {
            throw new RuntimeException('Gemini returned invalid JSON structure: '.json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function callGeminiJson(string $prompt): array
    {
        try {
            $response = Http::connectTimeout($this->connectTimeoutSeconds())
                ->retry([300, 900], throw: false, when: static fn (\Throwable $exception): bool => $exception instanceof ConnectionException)
                ->timeout($this->generationRequestTimeoutSeconds())
                ->post("{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            $response->throw();

            $text = $response->json('candidates.0.content.parts.0.text', '');
            $data = json_decode($text, true);

            if (! is_array($data)) {
                throw new RuntimeException('Gemini returned invalid JSON: '.$text);
            }

            return $data;
        } catch (ConnectionException $e) {
            throw new RuntimeException('Gemini request timed out or could not connect: '.$e->getMessage(), previous: $e);
        } catch (RequestException $e) {
            throw new RuntimeException('Gemini API error: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Translate explanation helper text into the target language while keeping
     * answers, German patterns, and German examples intact.
     *
     * @param  array<string, string|array<string, string>>  $explanations
     * @param  string  $language  ISO 639-1 code (ru, uk, en, tr, ar)
     * @return array<string, string|array<string, string>>
     *
     * @throws RuntimeException
     */
    public function translateExplanations(array $explanations, string $language): array
    {
        $languageNames = [
            'ru' => 'Russian',
            'uk' => 'Ukrainian',
            'en' => 'English',
            'tr' => 'Turkish',
            'ar' => 'Arabic',
        ];

        $languageName = $languageNames[$language] ?? 'Russian';
        $json = json_encode($explanations, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Translate the following JSON object values into {$languageName}.
Each JSON value is either:
- a string explanation, OR
- an object with fields like "answer", "rule_type", "reason", "pattern", "contrast", "example", OR
- an object with fields like "correct_answer", "reason", "evidence", "wrong_answer_reason", "strategy_hint".
Rules:
1. Keep all JSON keys exactly as-is.
2. If a value is a string, translate it fully into {$languageName}. Keep the leading German answer if it is already part of the sentence.
3. If a value is an object:
   - if the object has an "answer" field:
     - keep the "answer" field in German exactly as-is
     - add a new field "answer_translation" with a short, natural translation of the German "answer" word or phrase into {$languageName} (e.g. for "weshalb" in Russian: "поэтому; по какой причине")
     - keep the "pattern" field in German exactly as-is
     - keep the "example" field in German exactly as-is
     - translate only "rule_type", "reason", and "contrast" into {$languageName}
   - if the object has a "correct_answer" field:
     - keep the "correct_answer" value exactly as-is
     - translate "reason", "evidence", "wrong_answer_reason", and "strategy_hint" into {$languageName}
     - do not invent new keys
   - preserve the object shape and field names exactly
4. Return ONLY a valid JSON object. No markdown, no extra text.

Input:
{$json}
PROMPT;

        try {
            $response = Http::timeout($this->explanationRequestTimeoutSeconds())
                ->post("{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            $response->throw();

            $text = $response->json('candidates.0.content.parts.0.text', '');
            $data = json_decode($text, true);

            if (! is_array($data)) {
                throw new RuntimeException('Translation returned invalid JSON: '.$text);
            }

            return $data;
        } catch (RequestException $e) {
            throw new RuntimeException('Gemini API error: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Generate structured explanations for an existing question without changing its text or answers.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, array<string, string>>
     *
     * @throws RuntimeException
     */
    public function generateExplanations(array $content, string $topic = '', string $qualityRetryHint = ''): array
    {
        $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $topicLine = $topic !== '' ? "Thema/Titel der Aufgabe: {$topic}\n" : '';
        $allowedRuleTypes = implode(', ', QuestionGenerationQualityValidator::allowedRuleTypes());
        $retrySection = $qualityRetryHint !== '' ? "\n{$qualityRetryHint}\n" : '';

        $prompt = <<<PROMPT
Du bist ein erfahrener Nachhilfelehrer fuer telc Deutsch B2 Allgemein.

Erzeuge NUR strukturierte Erklaerungen fuer die Luecken einer bestehenden Sprachbausteine-Aufgabe. Veraendere NICHT den Text, NICHT die richtigen Antworten und NICHT die Optionen.
{$retrySection}

{$topicLine}Input-JSON:
{$json}

Gib NUR ein JSON-Objekt mit exakt denselben gap-Keys wie in "correct" zurueck, z.B.:
{
  "gap_1": {
    "answer": "dass",
    "rule_type": "Konjunktion",
    "reason": "Das Signal steckt im Verb 'einig': 'sich einig sein, dass...' ist eine feste Konstruktion fuer Fakten und Aussagen — kein Fragesatz, deshalb 'dass' und nicht 'ob'.",
    "pattern": "sich einig sein, dass ...",
    "contrast": "Typische Falle: 'ob' steht auch nach Verben der Kommunikation. Aber 'ob' leitet eine indirekte Ja/Nein-Frage ein ('Ich frage, ob...'). Hier ist keine Frage gemeint, sondern eine Aussage ueber einen Fakt → 'dass'.",
    "example": "Alle sind sich einig, dass mehr Investitionen noetig sind."
  }
}

Regeln fuer Inhalt und Stil:
- "answer" muss exakt der richtige deutsche Ausdruck aus "correct" sein.
- "rule_type" muss einer dieser Werte sein: {$allowedRuleTypes}
- Wenn es ein "options_pool" oder konkrete Lueckenoptionen gibt, waehle fuer "contrast" einen tatsaechlich vorhandenen alternativen Ausdruck aus diesen Optionen und nenne ihn woertlich.
- "rule_type" muss linguistisch zum Antwortwort passen. "ob", "dass", "wenn", "falls" sind Konjunktionen; Praepositionen duerfen nicht als Pronominaladverb bezeichnet werden.

STIL DER ERKLAERUNGEN — SEHR WICHTIG:
Schreibe wie ein erfahrener Nachhilfelehrer, der einem Freund erklaert, nicht wie ein Grammatikbuch.

- "reason": Zeige das Denkmuster. Was ist das SIGNAL im Satz, das die richtige Antwort anzeigt — das Verb links, ein Konnektorpaar, eine Praepositionalphrase?
  SCHLECHT: "Hier wird ein Nebensatz eingeleitet." oder "Das Adverb drueckt einen Gegensatz aus."
  GUT: "Schau links von der Luecke: 'warten' — dieses Verb verlangt immer 'auf + Akk.'. Das ist eine feste Rektion, kein Ermessensspielraum."
  GUT: "Im ersten Satzteil steht ein positives Urteil, direkt danach kommt eine Einschraenkung. Fuer diesen Kontrast innerhalb eines Arguments brauchst du 'jedoch'."
  Beginne NICHT mit "Hier wird..." oder "An dieser Stelle..." oder "Das Adverb/Pronomen/Wort drueckt aus...".

- "contrast": Erklaere die FALLE — warum ein Lernender den Distraktor waehlen koennte, und warum das ein Irrtum ist. Nenne den Distraktor woertlich.
  SCHLECHT: "'trotzdem' passt syntaktisch weniger gut."
  GUT: "Typische Falle: 'trotzdem' steht auch am Satzanfang und signalisiert Gegensatz. Aber 'trotzdem' heisst 'trotz ALLEM' und bezieht sich auf den ganzen Vorgaengersatz. 'Jedoch' zeigt einen feineren Kontrast innerhalb eines Arguments — genau das passiert hier."

- "pattern": Feste Konstruktion, Rektion oder Signalform, z.B. "warten auf + Akk." oder "nicht nur ..., sondern auch ...". Leerer String wenn wirklich keine vorliegt.

- "example": Alltagsnah und merkbar — kein Lehrbuch-Deutsch. Verwende nach Moeglichkeit dieselbe Konstruktion oder dasselbe Antwortwort.
  SCHLECHT: "Das Wort wird in diesem Kontext verwendet."
  GUT: "Er wartet schon seit einer Stunde auf seinen Freund."

- Return ONLY valid JSON.
PROMPT;

        $response = $this->callGeminiJson($prompt);

        return $response;
    }

    protected function currentTime(): float
    {
        return microtime(true);
    }

    protected function prepareInteractiveExecutionBudget(): void
    {
        $currentLimit = (int) ini_get('max_execution_time');
        $targetLimit = $this->requestBudgetSeconds() + 5;

        if ($currentLimit === 0 || $currentLimit >= $targetLimit) {
            return;
        }

        set_time_limit($targetLimit);
    }

    protected function requestBudgetSeconds(): int
    {
        return (int) config('services.gemini.request_budget_seconds', 120);
    }

    protected function connectTimeoutSeconds(): int
    {
        return (int) config('services.gemini.connect_timeout_seconds', 10);
    }

    protected function generationRequestTimeoutSeconds(): int
    {
        return (int) config('services.gemini.request_timeout_seconds', 45);
    }

    protected function explanationRequestTimeoutSeconds(): int
    {
        return (int) config('services.gemini.explanation_timeout_seconds', 30);
    }

    protected function maxGenerationAttempts(): int
    {
        return (int) config('services.gemini.max_generation_attempts', 3);
    }

    protected function remainingBudgetSeconds(float $deadline): float
    {
        return $deadline - $this->currentTime();
    }

    protected function shouldStopBeforeRetry(float $deadline): bool
    {
        return $this->remainingBudgetSeconds($deadline) < self::MINIMUM_RETRY_WINDOW_SECONDS;
    }

    /**
     * @param  array{
     *   passed: bool,
     *   retryable: bool,
     *   should_regenerate_explanations: bool,
     *   errors: list<string>,
     *   warnings: list<string>,
     *   review_gap_ids: list<string>,
     *   explanations_status: 'passed'|'needs_review'|'failed'
     * }  $qualityReport
     * @return array{
     *   passed: bool,
     *   retryable: bool,
     *   should_regenerate_explanations: bool,
     *   errors: list<string>,
     *   warnings: list<string>,
     *   review_gap_ids: list<string>,
     *   explanations_status: 'passed'|'needs_review'
     * }
     */
    protected function softenExplanationFailures(array $qualityReport): array
    {
        $warnings = $this->normalizeMessages($qualityReport['warnings'] ?? []);
        $errors = $this->normalizeMessages($qualityReport['errors'] ?? []);
        $explanationErrors = array_values(array_filter(
            $errors,
            static fn (string $error): bool => str_starts_with($error, 'Explanation for '),
        ));
        $reviewGapIds = array_values(array_unique([
            ...($qualityReport['review_gap_ids'] ?? []),
            ...$this->extractReviewGapIdsFromMessages([
                ...$warnings,
                ...$explanationErrors,
            ]),
        ]));

        return [
            'passed' => true,
            'retryable' => false,
            'should_regenerate_explanations' => false,
            'errors' => [],
            'warnings' => array_values(array_unique([
                ...$warnings,
                ...$explanationErrors,
                'Explanations need editorial review before publishing this question.',
            ])),
            'review_gap_ids' => $reviewGapIds,
            'explanations_status' => 'needs_review',
        ];
    }

    /**
     * @param  array{
     *   errors: list<string>,
     *   should_regenerate_explanations: bool
     * }  $qualityReport
     */
    protected function canBeSoftenedToExplanationReview(array $qualityReport): bool
    {
        if (! ($qualityReport['should_regenerate_explanations'] ?? false)) {
            return false;
        }

        $errors = $this->normalizeMessages($qualityReport['errors'] ?? []);

        if ($errors === []) {
            return true;
        }

        foreach ($errors as $error) {
            if (
                ! str_starts_with($error, 'Explanation for ')
                && $error !== 'Generated question is missing structured explanations.'
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    protected function normalizeMessages(mixed $messages): array
    {
        if (! is_array($messages)) {
            return [];
        }

        $normalized = [];

        foreach ($messages as $message) {
            if (is_string($message)) {
                $trimmed = trim($message);

                if ($trimmed !== '') {
                    $normalized[] = $trimmed;
                }

                continue;
            }

            if (is_scalar($message) || $message === null) {
                $trimmed = trim((string) $message);

                if ($trimmed !== '') {
                    $normalized[] = $trimmed;
                }
            }
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $messages
     * @return list<string>
     */
    protected function extractReviewGapIdsFromMessages(array $messages): array
    {
        $gapIds = [];

        foreach ($messages as $message) {
            if (preg_match('/Explanation for (gap_\d+)/', $message, $matches) === 1) {
                $gapIds[] = $matches[1];
            }
        }

        return array_values(array_unique($gapIds));
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function countGeneratedContentWords(array $content, string $format): int
    {
        if (in_array($format, [
            ListeningTeilOneSegmentedContent::FORMAT,
            'listening_short_true_false',
            'listening_long_true_false',
        ], true)) {
            return self::countTextWords((string) ($content['transcript'] ?? ''));
        }

        if ($format === 'reading_article_mc') {
            $article = is_array($content['article'] ?? null) ? $content['article'] : [];

            return self::countTextWords((string) ($article['body'] ?? ''));
        }

        return self::countTextWords((string) ($content['text'] ?? ''));
    }

    /**
     * @param  array{
     *   passed: bool,
     *   retryable: bool,
     *   should_regenerate_explanations: bool,
     *   errors: list<string>,
     *   warnings: list<string>,
     *   review_gap_ids: list<string>,
     *   explanations_status: 'passed'|'needs_review'|'failed'
     * }  $qualityReport
     * @param  array<string, mixed>  $content
     * @return array{
     *   passed: bool,
     *   retryable: bool,
     *   should_regenerate_explanations: bool,
     *   errors: list<string>,
     *   warnings: list<string>,
     *   review_gap_ids: list<string>,
     *   explanations_status: 'passed'|'needs_review'|'failed'
     * }
     */
    private function applyGeneratedDraftRequirements(array $qualityReport, array $content, string $format, array $options = []): array
    {
        if (! in_array($format, [
            ListeningTeilOneSegmentedContent::FORMAT,
            'listening_short_true_false',
            'listening_long_true_false',
        ], true)) {
            return $qualityReport;
        }

        $errors = $this->normalizeMessages($qualityReport['errors'] ?? []);
        $warnings = $this->normalizeMessages($qualityReport['warnings'] ?? []);
        $audio = is_array($content['audio'] ?? null) ? $content['audio'] : [];
        $transcript = trim((string) ($content['transcript'] ?? ''));
        $segments = is_array($content['segments'] ?? null) ? array_values(array_filter($content['segments'], 'is_array')) : [];
        $context = is_array($content['context'] ?? null) ? $content['context'] : [];
        $statementCount = count(is_array($content['statements'] ?? null) ? $content['statements'] : []);
        $wordCount = self::countTextWords($transcript);
        $minimumWordCount = match ($format) {
            ListeningTeilOneSegmentedContent::FORMAT => 280,
            'listening_long_true_false' => 320,
            default => 45,
        };
        $expectedStatementCount = $format === 'listening_long_true_false' ? 10 : 5;

        if ($transcript === '') {
            $errors[] = match ($format) {
                ListeningTeilOneSegmentedContent::FORMAT => 'Listening segmented draft must include a derived transcript.',
                'listening_long_true_false' => 'Listening long draft must include a transcript.',
                default => 'Listening short draft must include a transcript.',
            };
        }

        if ($wordCount < $minimumWordCount) {
            $errors[] = match ($format) {
                ListeningTeilOneSegmentedContent::FORMAT => "Listening segmented transcript is too short ({$wordCount} words, expected at least {$minimumWordCount}).",
                'listening_long_true_false' => "Listening long transcript is too short ({$wordCount} words, expected at least {$minimumWordCount}).",
                default => "Listening short transcript is too short ({$wordCount} words, expected at least {$minimumWordCount}).",
            };
        }

        if ($statementCount !== $expectedStatementCount) {
            $errors[] = match ($format) {
                ListeningTeilOneSegmentedContent::FORMAT => 'Listening segmented draft must contain exactly 5 statements.',
                'listening_long_true_false' => 'Listening long draft must contain exactly 10 statements.',
                default => 'Listening short draft must contain exactly 5 statements.',
            };
        }

        if (blank($audio['audio_notes'] ?? null)) {
            $errors[] = match ($format) {
                ListeningTeilOneSegmentedContent::FORMAT => 'Listening segmented draft must include audio_notes.',
                'listening_long_true_false' => 'Listening long draft must include audio_notes.',
                default => 'Listening short draft must include audio_notes.',
            };
        } elseif ($format === 'listening_long_true_false') {
            if (preg_match('/\b(rundfunk-?interview|interview|moderator|gast|gespraech)\b/ui', (string) $audio['audio_notes']) !== 1) {
                $warnings[] = 'Listening long audio_notes should explicitly mention interview framing (Moderator/Gast).';
            }
        } elseif (preg_match('/\b(dialog|gespraech|interview|moderator|gast|rolle|rollenspiel)\b/ui', (string) $audio['audio_notes']) === 1) {
            $warnings[] = $format === ListeningTeilOneSegmentedContent::FORMAT
                ? 'Listening segmented audio_notes should describe a short one-voice news bulletin, not a dialogue.'
                : 'Listening short audio_notes should describe a neutral one-voice bulletin, not a dialogue.';
        }

        if ($format === ListeningTeilOneSegmentedContent::FORMAT) {
            $intro = is_array($content['intro'] ?? null) ? $content['intro'] : [];

            if (blank($intro['text'] ?? null)) {
                $errors[] = 'Listening segmented draft must include an anchor intro.';
            }

            if (count($segments) !== 5) {
                $errors[] = 'Listening segmented draft must contain exactly 5 news segments.';
            }

            foreach ($segments as $index => $segment) {
                $segmentText = trim((string) ($segment['segment_text'] ?? ''));
                $statementText = trim((string) ($segment['statement_text'] ?? ''));

                if ($segmentText === '') {
                    $errors[] = 'Each listening segmented draft item must contain segment_text.';
                    $warnings[] = 'Broken segment index: '.($index + 1).'.';
                }

                if ($statementText === '') {
                    $errors[] = 'Each listening segmented draft item must contain statement_text.';
                    $warnings[] = 'Broken statement index: '.($index + 1).'.';
                }
            }
        }

        if ($format === 'listening_long_true_false') {
            $instructions = trim((string) ($content['instructions'] ?? ''));
            $audioTitle = trim((string) ($audio['title'] ?? ''));
            $audioNotes = trim((string) ($audio['audio_notes'] ?? ''));
            $speaker = trim((string) ($context['speaker'] ?? ''));
            $replayLimit = (int) ($context['replay_limit'] ?? 0);

            if (preg_match('/\b(interview|rundfunk-?interview)\b/ui', $instructions) !== 1) {
                $errors[] = 'Listening long draft instructions must explicitly describe an interview.';
            }

            if (
                preg_match('/\b(rundfunk-?interview|interview)\b/ui', $audioTitle) !== 1
                && preg_match('/\b(rundfunk-?interview|interview)\b/ui', $audioNotes) !== 1
            ) {
                $warnings[] = 'Listening long draft should include interview framing in audio title or audio notes.';
            }

            if (preg_match('/\b(nachrichtensendung|fuenf kurze texte|fünf kurze texte|ansage|ansagen|kurznachrichten|kurzmeldung)\b/ui', $transcript) === 1) {
                $errors[] = 'Listening long transcript must not use Teil 1/Teil 3 framing markers.';
            }

            if (preg_match('/\b(Moderator|Gast|Interviewer|Interviewerin)\b/u', $transcript) !== 1) {
                $warnings[] = 'Listening long transcript should clearly indicate interview speaker turns.';
            }

            if ($speaker === '') {
                $errors[] = 'Listening long draft must include context.speaker.';
            }

            if ($replayLimit !== 1) {
                $warnings[] = 'Listening long draft should keep context.replay_limit set to 1.';
            }
        }

        [$errors, $warnings] = $this->applyTelcHoerenCanonLock(
            $errors,
            $warnings,
            $content,
            $format,
            trim((string) ($options['module_slug'] ?? '')),
        );
        [$errors, $warnings] = $this->applyTelcHoerenTeilTwoCanonLock(
            $errors,
            $warnings,
            $content,
            $format,
            trim((string) ($options['module_slug'] ?? '')),
        );

        $passed = $errors === [];

        return [
            'passed' => $passed,
            'retryable' => $qualityReport['retryable'] || ! $passed,
            'should_regenerate_explanations' => $qualityReport['should_regenerate_explanations'],
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'review_gap_ids' => $qualityReport['review_gap_ids'],
            'explanations_status' => $qualityReport['explanations_status'],
        ];
    }

    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @return array{0: list<string>, 1: list<string>}
     */
    private function applyTelcHoerenCanonLock(array $errors, array $warnings, array $content, string $format, string $moduleSlug): array
    {
        if ($moduleSlug !== 'hoeren-teil-1') {
            return [$errors, $warnings];
        }

        if ($format !== ListeningTeilOneSegmentedContent::FORMAT) {
            $errors[] = 'Canonical Hören Teil 1 lock: module hoeren-teil-1 must use listening_segmented_true_false.';

            return [$errors, $warnings];
        }

        $instructions = trim((string) ($content['instructions'] ?? ''));
        $audio = is_array($content['audio'] ?? null) ? $content['audio'] : [];
        $intro = is_array($content['intro'] ?? null) ? $content['intro'] : [];
        $segments = is_array($content['segments'] ?? null) ? array_values(array_filter($content['segments'], 'is_array')) : [];
        $statements = is_array($content['statements'] ?? null) ? array_values(array_filter($content['statements'], 'is_array')) : [];
        $transcript = trim((string) ($content['transcript'] ?? ''));
        $audioTitle = trim((string) ($audio['title'] ?? ''));
        $audioNotes = trim((string) ($audio['audio_notes'] ?? ''));
        $introText = trim((string) ($intro['text'] ?? ''));
        $introVoiceProfile = trim((string) ($intro['voice_profile'] ?? ''));

        if (! $this->containsNeedle($instructions, 'nachrichtensendung')) {
            $errors[] = 'Canonical Hören Teil 1 lock: instructions must explicitly say Nachrichtensendung.';
        }

        if ($this->containsForbiddenHoerenTeilOneMarker($instructions)) {
            $errors[] = 'Canonical Hören Teil 1 lock: instructions must not use Teil 2/Teil 3 framing (Interview, Ansagen, fünf kurze Texte).';
        }

        if ($this->containsForbiddenHoerenTeilOneMarker($audioTitle) || $this->containsForbiddenHoerenTeilOneMarker($audioNotes)) {
            $errors[] = 'Canonical Hören Teil 1 lock: audio title/notes must stay in Nachrichtensendung framing.';
        }

        if ($this->containsForbiddenHoerenTeilOneMarker($introText) || $this->containsForbiddenHoerenTeilOneMarker($transcript)) {
            $errors[] = 'Canonical Hören Teil 1 lock: intro/transcript must not include Teil 2/Teil 3 framing markers.';
        }

        if ($introVoiceProfile !== 'anchor_main') {
            $errors[] = 'Canonical Hören Teil 1 lock: intro.voice_profile must be anchor_main.';
        }

        foreach ($segments as $index => $segment) {
            $position = $index + 1;
            $voiceProfile = trim((string) ($segment['voice_profile'] ?? ''));
            $statementId = trim((string) ($segment['statement_id'] ?? ''));

            if ($voiceProfile !== 'news_main') {
                $errors[] = "Canonical Hören Teil 1 lock: segment {$position} must use voice_profile news_main.";
            }

            if ($statementId !== "statement_{$position}") {
                $errors[] = "Canonical Hören Teil 1 lock: segment {$position} must map to statement_{$position}.";
            }
        }

        foreach ($statements as $index => $statement) {
            $position = $index + 1;
            $statementId = trim((string) ($statement['id'] ?? ''));

            if ($statementId !== "statement_{$position}") {
                $errors[] = "Canonical Hören Teil 1 lock: statements must stay ordered from statement_1 to statement_5 (broken at {$position}).";
            }
        }

        return [
            array_values(array_unique($errors)),
            array_values(array_unique($warnings)),
        ];
    }

    private function containsNeedle(string $text, string $needle): bool
    {
        return mb_stripos($text, $needle) !== false;
    }

    private function containsForbiddenHoerenTeilOneMarker(string $text): bool
    {
        if ($text === '') {
            return false;
        }

        return preg_match('/\b(rundfunk-?interview|interview|fuenf kurze texte|fünf kurze texte|kurze texte|ansage|ansagen|durchsage|durchsagen|kurznachrichten|kurzmeldung|kurze meldungen)\b/ui', $text) === 1;
    }

    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @return array{0: list<string>, 1: list<string>}
     */
    private function applyTelcHoerenTeilTwoCanonLock(array $errors, array $warnings, array $content, string $format, string $moduleSlug): array
    {
        if ($moduleSlug !== 'hoeren-teil-2') {
            return [$errors, $warnings];
        }

        if ($format !== 'listening_long_true_false') {
            $errors[] = 'Canonical Hören Teil 2 lock: module hoeren-teil-2 must use listening_long_true_false.';

            return [$errors, $warnings];
        }

        $instructions = trim((string) ($content['instructions'] ?? ''));
        $audio = is_array($content['audio'] ?? null) ? $content['audio'] : [];
        $transcript = trim((string) ($content['transcript'] ?? ''));
        $audioTitle = trim((string) ($audio['title'] ?? ''));
        $audioNotes = trim((string) ($audio['audio_notes'] ?? ''));

        if (preg_match('/\b(interview|rundfunk-?interview)\b/ui', $instructions) !== 1) {
            $errors[] = 'Canonical Hören Teil 2 lock: instructions must explicitly say Interview.';
        }

        if (
            preg_match('/\b(interview|rundfunk-?interview)\b/ui', $audioTitle) !== 1
            && preg_match('/\b(interview|rundfunk-?interview)\b/ui', $audioNotes) !== 1
        ) {
            $warnings[] = 'Canonical Hören Teil 2 lock: audio title/notes should stay in interview framing.';
        }

        if (preg_match('/\b(nachrichtensendung|fuenf kurze texte|fünf kurze texte|ansage|ansagen|kurznachrichten|kurzmeldung)\b/ui', $transcript) === 1) {
            $errors[] = 'Canonical Hören Teil 2 lock: transcript must not include Teil 1/Teil 3 framing markers.';
        }

        return [
            array_values(array_unique($errors)),
            array_values(array_unique($warnings)),
        ];
    }

    private function buildPrompt(array $options, string $retryHint = ''): string
    {
        $format = $options['format'] ?? 'per_gap';
        $difficulty = $options['difficulty'] ?? 'medium';
        $topicSeed = trim((string) ($options['topic_seed'] ?? $options['topic_hint'] ?? ''));
        $topicCatalogTitle = trim((string) ($options['topic_catalog_title'] ?? ''));
        $goldenExample = trim((string) ($options['golden_example'] ?? ''));

        $topicInstruction = $topicSeed !== ''
            ? "Nutze dieses kuratierte Themenmuster fuer telc Deutsch B2 Allgemein: {$topicCatalogTitle}. {$topicSeed}"
            : 'Waehle ein alltagliches B2-Thema (z.B. Wohnen, Arbeit, Reisen, Gesundheit, Technologie, Umwelt).';

        if ($format === 'shared_pool') {
            return $this->buildSharedPoolPrompt($difficulty, $topicInstruction, $retryHint, $goldenExample);
        }

        if ($format === ListeningTeilOneSegmentedContent::FORMAT) {
            return $this->buildListeningSegmentedTrueFalsePrompt($difficulty, $topicInstruction, $retryHint, $goldenExample);
        }

        if ($format === 'listening_short_true_false') {
            return $this->buildListeningShortTrueFalsePrompt($difficulty, $topicInstruction, $retryHint, $goldenExample);
        }

        if ($format === 'listening_long_true_false') {
            return $this->buildListeningLongTrueFalsePrompt($difficulty, $topicInstruction, $retryHint, $goldenExample);
        }

        return $this->buildPerGapPrompt($difficulty, $topicInstruction, $retryHint, $goldenExample);
    }

    private function buildPerGapPrompt(string $difficulty, string $topicInstruction, string $retryHint = '', string $goldenExample = ''): string
    {
        $retrySection = $retryHint ? "\n{$retryHint}\n" : '';
        $allowedRuleTypes = implode(', ', QuestionGenerationQualityValidator::allowedRuleTypes());
        $goldenExampleSection = $goldenExample !== ''
            ? "\nStilorientierung (NUR fuer Ton, Laenge und Natuerlichkeit — NICHT inhaltlich kopieren):\n---\n{$goldenExample}\n---\n"
            : '';

        return <<<PROMPT
Du bist ein erfahrener telc-Pruefungsautor fuer Deutsch B2 Allgemein mit 15 Jahren Berufserfahrung. Du schreibst Texte, die natuerlich klingen und echte kommunikative Situationen widerspiegeln — kein Lehrbuch-Deutsch.
{$goldenExampleSection}
Erstelle eine Sprachbausteine Teil 1 Uebungsaufgabe. Der offizielle Aufgabentyp ist ein Lueckentext, meist eine E-Mail, mit 10 Luecken und genau 3 Optionen pro Luecke.
{$retrySection}
Anforderungen an Text und Aufgabe:
- {$topicInstruction}
- Schwierigkeitsgrad: {$difficulty}
- Der topic/title im JSON soll wie ein kurzer redaktioneller Titel wirken und das Themenmuster konkretisieren, nicht nur wiederholen.
- Ziellaenge: 260 Woerter (OHNE die gap-Marker gezaehlt). Akzeptabler Bereich: 220-300 Woerter. Texte unter 220 oder ueber 300 Woerter sind NICHT akzeptabel.
- Der Text MUSS wie eine echte E-Mail oder halbformelle Mitteilung klingen — mit konkretem kommunikativem Ziel (Anfrage, Beschwerde, Einladung, Bericht etc.).
- Der Text MUSS eine vollstaendige Struktur haben: Anrede, Einleitung, Hauptteil, Schlussformel. Mindestens 4 Absaetze insgesamt.
- VERMEIDE: uebermaeßig formelle Sprache, die kein echter Mensch so schreiben wuerde; Wiederholung derselben Satzstruktur in jedem Satz; Texte, die wie eine Grammatikuebung klingen statt wie echte Kommunikation.
- Genau 10 Luecken: {{gap_1}} bis {{gap_10}}
- Die Luecken sollen typische B2-Grammatik und Funktionswoerter testen: Deklinationen, Artikelsetzung, Praepositionen, Pronomen, Konjunktionen, Pronominaladverbien, Verben, Partikeln und feste Konstruktionen.
- Fuer jede Luecke: genau 3 Optionen (1 richtige + 2 plausible Distraktoren). Distraktoren sollen einen typischen Fehler abbilden — idealerweise gleiche Wortart, gleiche syntaktische Position, aber falsche Rektion, falscher Kasus oder falsche semantische Funktion.
- Erfinde KEINE versteckten Bezugswoerter, impliziten Faktoren oder unausgesprochenen Nomen, um eine Luecke zu erklaeren. Begruende nur mit dem, was im sichtbaren Satz wirklich steht.
- Pruefe fuer jede Luecke vor dem Zurueckgeben: Wenn du die richtige Antwort einsetzt, muss der ganze Satz natuerlich, idiomatisch und grammatisch korrekt klingen.
- Besonders bei Relativpronomen, Konjunktionen und Praepositionen darfst du keine lochrige oder halb-korrigierte Satzstruktur erzeugen.

Anforderungen an Erklaerungen — STIL SEHR WICHTIG:
- WICHTIG: Begruende (reason) detailliert und logisch. Fuer contrast schreibe immer einen vollstaendigen Erklaerungssatz, warum der Distraktor falsch ist (nicht nur "passt nicht"). Beispiele muessen grammatikalisch korrekte vollstaendige Saetze sein.
- Zulaessige rule_type-Werte: {$allowedRuleTypes}
- Der rule_type muss linguistisch zum Antwortwort passen. "ob"/"dass"/"wenn"/"falls" sind Konjunktionen, Praepositionen duerfen nicht als Pronominaladverb etikettiert werden.
- Schreibe wie ein erfahrener Nachhilfelehrer, der einem Freund erklaert — nicht wie ein Grammatikbuch.
- "reason": Zeige das Denkmuster. Was ist das SIGNAL im Satz, das die richtige Antwort anzeigt — das Verb links, ein Konnektorpaar, eine feste Konstruktion?
  SCHLECHT: "Das Adverb drueckt einen Gegensatz aus." oder "Hier wird ein Nebensatz eingeleitet."
  GUT: "Schau links von der Luecke: 'warten' — dieses Verb verlangt immer 'auf + Akk.'. Das ist eine feste Rektion." oder "Im ersten Satzteil steht ein positives Urteil, direkt danach kommt eine Einschraenkung. Fuer diesen Kontrast brauchst du 'jedoch'."
  Beginne NICHT mit "Hier wird...", "An dieser Stelle...", "Das Adverb/Pronomen drueckt aus...".
- "contrast": Erklaere die FALLE — warum ein Lernender den Distraktor waehlen koennte, und warum das ein Irrtum ist. Nenne den Distraktor woertlich.
  SCHLECHT: "'trotzdem' passt hier nicht." oder "'ob' ist falsch."
  GUT: "Typische Falle: 'ob' steht auch nach Verben der Kommunikation. Aber 'ob' leitet eine Ja/Nein-Frage ein — hier ist keine Frage gemeint, sondern eine Aussage ueber einen Fakt → 'dass'."
- "pattern": Feste Konstruktion, Rektion oder Signalform wenn vorhanden, z.B. "warten auf + Akk." oder "nicht nur ..., sondern auch ...". Leerer String wenn keine vorliegt.
- "example": Alltagsnah und merkbar — kein Lehrbuch-Deutsch. Verwende dieselbe Konstruktion oder dasselbe Antwortwort.

Antworte NUR mit einem JSON-Objekt in diesem Format (kein Markdown, kein Text davor/danach):
{
  "topic": "Kurze Beschreibung des Brieftyps und Themas (max 60 Zeichen)",
  "difficulty": "{$difficulty}",
  "content": {
    "text": "Vollstaendiger Brieftext mit {{gap_1}} bis {{gap_10}} an den richtigen Stellen. Absaetze mit \\n\\n trennen.",
    "options": {
      "gap_1": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"],
      "gap_2": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"],
      "gap_3": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"],
      "gap_4": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"],
      "gap_5": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"],
      "gap_6": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"],
      "gap_7": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"],
      "gap_8": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"],
      "gap_9": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"],
      "gap_10": ["richtiges_Wort", "Distraktor_1", "Distraktor_2"]
    },
    "correct": {
      "gap_1": "richtiges_Wort",
      "gap_2": "richtiges_Wort",
      "gap_3": "richtiges_Wort",
      "gap_4": "richtiges_Wort",
      "gap_5": "richtiges_Wort",
      "gap_6": "richtiges_Wort",
      "gap_7": "richtiges_Wort",
      "gap_8": "richtiges_Wort",
      "gap_9": "richtiges_Wort",
      "gap_10": "richtiges_Wort"
    },
    "explanation": {
      "gap_1": {
        "answer": "richtiges_Wort",
        "rule_type": "z.B. Verb mit Praeposition",
        "reason": "Konkrete Erklaerung, warum die Loesung in DIESEM Satz grammatisch und semantisch passt.",
        "pattern": "z.B. warten auf + Akk.",
        "contrast": "Konkreter Hinweis, warum ein plausibler Distraktor hier nicht passt.",
        "example": "Ein neuer kurzer Beispielsatz mit derselben Regel."
      },
      "gap_2": {
        "answer": "richtiges_Wort",
        "rule_type": "z.B. Doppelkonnektor",
        "reason": "Praezise Erklaerung...",
        "pattern": "nicht nur ..., sondern auch ...",
        "contrast": "Warum ein anderer Konnektor hier scheitert.",
        "example": "Ein neuer Beispielsatz."
      }
    }
  }
}

WICHTIG: Das richtige Wort in "options" kann an beliebiger Position stehen (nicht immer an erster Stelle). Mische die Reihenfolge der Optionen zufaellig.
PROMPT;
    }

    private function buildSharedPoolPrompt(string $difficulty, string $topicInstruction, string $retryHint = '', string $goldenExample = ''): string
    {
        $retrySection = $retryHint ? "\n{$retryHint}\n" : '';
        $allowedRuleTypes = implode(', ', QuestionGenerationQualityValidator::allowedRuleTypes());
        $goldenExampleSection = $goldenExample !== ''
            ? "\nStilorientierung (NUR fuer Ton, Laenge und Natuerlichkeit — NICHT inhaltlich kopieren):\n---\n{$goldenExample}\n---\n"
            : '';

        return <<<PROMPT
Du bist ein erfahrener telc-Pruefungsautor fuer Deutsch B2 Allgemein mit 15 Jahren Berufserfahrung. Du schreibst Sachtexte in journalistischem Stil, die sachlich und zugleich allgemeinverstaendlich klingen.
{$goldenExampleSection}
Erstelle eine Sprachbausteine Teil 2 Uebungsaufgabe. Der offizielle Aufgabentyp ist ein Zeitschriftentext von allgemeinem Interesse mit 10 Luecken und 15 angebotenen Woertern oder Ausdruecken, von denen 5 unbenutzt bleiben.
{$retrySection}
Anforderungen an Text und Aufgabe:
- {$topicInstruction}
- Schwierigkeitsgrad: {$difficulty}
- Der topic/title im JSON soll wie ein kurzer redaktioneller Titel wirken und das Themenmuster konkretisieren, nicht nur wiederholen.
- Ziellaenge: 290 Woerter (OHNE die gap-Marker gezaehlt). Akzeptabler Bereich: 260-330 Woerter. Texte unter 260 oder ueber 330 Woerter sind NICHT akzeptabel.
- Der Text MUSS mindestens 3 inhaltliche Absaetze enthalten (je 4-6 Saetze), die das Thema aus verschiedenen Blickwinkeln beleuchten.
- VERMEIDE: Texte, die wie ein Schulaufsatz klingen; alle Absaetze mit derselben Struktur; zu viele einfache Hauptsaetze hintereinander.
- Genau 10 Luecken: {{gap_1}} bis {{gap_10}}
- Die Luecken sollen EINTEILIGE Konnektoren, Adverbien und Funktionswoerter testen: Konjunktionen, Adverbien, Pronominaladverbien, Partikeln und feste Ausdruecke.
- VERBOTEN als Gap-Antworten: Doppelkonnektoren wie "nicht nur...sondern auch", "sowohl...als auch", "zwar...aber". Verwende NUR einteilige Woerter oder Ausdruecke, die allein in eine Luecke passen (z.B. jedoch, allerdings, deshalb, ausserdem, zunaechst).
- Genau 15 Woerter im gemeinsamen Pool (options_pool): 10 richtige Antworten + 5 Distraktoren.
- WICHTIG fuer den Pool: Mindestens 7 der 15 Pool-Woerter sollen Konnektoren oder Adverbien sein (z.B. jedoch, allerdings, deshalb, ausserdem, zunaechst, insbesondere, dennoch, daher, folglich). Verteile auf verschiedene Funktionskategorien: Kontrast, Konsequenz, Ergaenzung, Temporal, Betonung.
- Alle Pool-Woerter stehen in ihrer endgueltigen Form — keine Konjugation oder Deklination ist noetig.
- Jede richtige Antwort aus "correct" MUSS woertlich auch im options_pool vorkommen. Pruefe vor dem Zurueckgeben still selbst, dass alle 10 correct-Werte exakt im Pool enthalten sind.
- Die Distraktoren muessen typische Fehlentscheidungen provozieren — gleiche semantische Kategorie, aber andere logische Funktion im Satz.
- KRITISCH: Pruefe jeden Distraktor — koennte er grammatisch auch in eine der 10 Luecken passen? Wenn ja, aendere den Satz oder tausche den Distraktor aus. Fuer jede Luecke darf NUR EIN Wort aus dem Pool passen.
- Erfinde KEINE versteckten Bezugswoerter, impliziten Faktoren oder unausgesprochenen Nomen, um eine Luecke zu erklaeren. Begruende nur mit dem, was im sichtbaren Satz wirklich steht.
- Verwende Relativpronomen wie "der/die/das" im Zweifel NICHT. Nutze sie nur, wenn direkt im sichtbaren Satz links davon ein eindeutiges Bezugswort steht, das in Genus und Numerus klar passt.

Anforderungen an Erklaerungen — STIL SEHR WICHTIG:
- Zulaessige rule_type-Werte: {$allowedRuleTypes}
- Der rule_type muss linguistisch zum Antwortwort passen. "ob"/"dass"/"wenn"/"falls" sind Konjunktionen, Praepositionen duerfen nicht als Pronominaladverb etikettiert werden.
- Schreibe wie ein erfahrener Nachhilfelehrer, der einem Freund erklaert — nicht wie ein Grammatikbuch.
- "reason": Zeige das Denkmuster. Was ist das SIGNAL im Satz, das die richtige Antwort anzeigt?
  SCHLECHT: "Das Adverb drueckt einen Gegensatz aus." oder "Hier wird ein Nebensatz eingeleitet."
  GUT: "Im ersten Satz steht eine positive Aussage, direkt danach kommt eine Einschraenkung. Fuer diesen Kontrast innerhalb eines Arguments brauchst du 'jedoch'."
  Beginne NICHT mit "Hier wird...", "An dieser Stelle...", "Das Adverb/Pronomen drueckt aus...".
- "contrast": Erklaere die FALLE — warum ein Lernender den Distraktor aus dem Pool waehlen koennte, und warum das ein Irrtum ist.
  SCHLECHT: "'trotzdem' passt hier nicht."
  GUT: "Typische Falle: 'trotzdem' drueckt auch Gegensatz aus und steht am Satzanfang. Aber 'trotzdem' bezieht sich auf den ganzen Vorgaengersatz ('trotz allem'). 'Jedoch' zeigt einen feineren internen Kontrast — genau das passiert hier."
- "pattern": Feste Konstruktion oder Signalform wenn vorhanden. Leerer String wenn keine vorliegt.
- "example": Alltagsnah und merkbar — kein Lehrbuch-Deutsch.

- Fuehre vor der Ausgabe eine stille Endkontrolle aus:
  1. Textlaenge fuer Teil 2 liegt sicher ueber 290 Woertern.
  2. options_pool hat genau 15 eindeutige Eintraege.
  3. alle 10 correct-Werte stehen exakt im options_pool.
  4. kein gap mit Relativpronomen hat ein zweifelhaftes oder verstecktes Bezugswort.

Antworte NUR mit einem JSON-Objekt in diesem Format (kein Markdown, kein Text davor/danach):
{
  "topic": "Kurze Beschreibung des Textthemas (max 60 Zeichen)",
  "difficulty": "{$difficulty}",
  "content": {
    "format": "shared_pool",
    "text": "Vollstaendiger Sachtext mit {{gap_1}} bis {{gap_10}} an den richtigen Stellen. Absaetze mit \\n\\n trennen.",
    "options_pool": ["wort1", "wort2", "wort3", "wort4", "wort5", "wort6", "wort7", "wort8", "wort9", "wort10", "wort11", "wort12", "wort13", "wort14", "wort15"],
    "correct": {
      "gap_1": "richtiges_Wort",
      "gap_2": "richtiges_Wort",
      "gap_3": "richtiges_Wort",
      "gap_4": "richtiges_Wort",
      "gap_5": "richtiges_Wort",
      "gap_6": "richtiges_Wort",
      "gap_7": "richtiges_Wort",
      "gap_8": "richtiges_Wort",
      "gap_9": "richtiges_Wort",
      "gap_10": "richtiges_Wort"
    },
    "explanation": {
      "gap_1": {
        "answer": "richtiges_Wort",
        "rule_type": "z.B. Kausalkonjunktion",
        "reason": "Praezise Erklaerung, warum die Loesung im Kontext passt.",
        "pattern": "",
        "contrast": "Konkreter Hinweis, warum eine plausible Alternative nicht passt.",
        "example": "Ein neuer kurzer Beispielsatz."
      },
      "gap_2": {
        "answer": "richtiges_Wort",
        "rule_type": "z.B. Relativpronomen",
        "reason": "Praezise Erklaerung...",
        "pattern": "z.B. Bezug auf ein Nomen im Nominativ",
        "contrast": "Warum ein anderes Relativwort nicht passt.",
        "example": "Ein neuer Beispielsatz."
      }
    }
  }
}

WICHTIG: Die options_pool sollen gemischt sein - nicht in der Reihenfolge der richtigen Antworten.
PROMPT;
    }

    private function buildListeningShortTrueFalsePrompt(string $difficulty, string $topicInstruction, string $retryHint = '', string $goldenExample = ''): string
    {
        $retrySection = $retryHint ? "\n{$retryHint}\n" : '';
        $goldenExampleSection = $goldenExample !== ''
            ? "\nStilorientierung (NUR fuer Ton und Struktur — NICHT inhaltlich kopieren):\n---\n{$goldenExample}\n---\n"
            : '';

        return <<<PROMPT
Du bist ein erfahrener telc-Pruefungsautor fuer Deutsch B2 Allgemein.
{$goldenExampleSection}
Erstelle eine Hören Teil 1 Uebungsaufgabe fuer ein kurzes, neutral gesprochenes Nachrichten- oder Servicebulletin mit genau 5 Aussagen richtig/falsch.
{$retrySection}

Verpflichtende Regeln:
- {$topicInstruction}
- Schwierigkeitsgrad: {$difficulty}
- Es gibt genau EINE Stimme.
- Kein Dialog, kein Interview, keine Rollen.
- Der Ton ist neutral, sachlich, klar und gut verstaendlich.
- Der transcript soll wie ein kurzer Nachrichten- oder Serviceblock klingen.
- Der transcript muss 5 getrennte Informationen enthalten, aus denen genau 5 Aussagen bewertet werden.
- Ziellaenge fuer den transcript: 45 bis 90 Woerter.
- Die Aussagen muessen sich eng auf den transcript beziehen, aber nicht nur wortgleich kopieren.
- Jede Aussage muss eindeutig true oder false sein.
- Zu jeder Aussage braucht es eine explanation mit:
  - correct_answer
  - reason
  - evidence
- Der topic/title soll wie ein kurzer Radio- oder Serviceblock-Titel klingen.
- audio_notes muessen ausdruecklich einen neutral gelesenen, kurzen Informationsblock mit einer Stimme beschreiben.

Antworte NUR mit einem JSON-Objekt in diesem Format:
{
  "topic": "Kurzer Titel des Audio-Blocks",
  "difficulty": "{$difficulty}",
  "content": {
    "format": "listening_short_true_false",
    "instructions": "Sie hören eine kurze Nachrichtensendung. Entscheiden Sie bei den Aussagen, ob sie richtig oder falsch sind.",
    "audio": {
      "title": "Kurzer Audiotitel",
      "audio_notes": "Kurze Nachrichtensendung mit neutraler Stimme und klar getrennten Meldungen."
    },
    "transcript": "Kurzer Nachrichten- oder Serviceblock mit genau einer Stimme.",
    "statements": [
      { "id": "statement_1", "number": 1, "text": "Aussage 1" },
      { "id": "statement_2", "number": 2, "text": "Aussage 2" },
      { "id": "statement_3", "number": 3, "text": "Aussage 3" },
      { "id": "statement_4", "number": 4, "text": "Aussage 4" },
      { "id": "statement_5", "number": 5, "text": "Aussage 5" }
    ],
    "correct": {
      "statement_1": "true",
      "statement_2": "false",
      "statement_3": "true",
      "statement_4": "false",
      "statement_5": "true"
    },
    "explanation": {
      "statement_1": {
        "correct_answer": "true",
        "reason": "Kurze klare Begruendung, warum die Aussage stimmt oder nicht stimmt.",
        "evidence": "Der konkrete Hoerhinweis aus dem transcript."
      },
      "statement_2": {
        "correct_answer": "false",
        "reason": "Kurze klare Begruendung, warum die Aussage stimmt oder nicht stimmt.",
        "evidence": "Der konkrete Hoerhinweis aus dem transcript."
      },
      "statement_3": {
        "correct_answer": "true",
        "reason": "Kurze klare Begruendung, warum die Aussage stimmt oder nicht stimmt.",
        "evidence": "Der konkrete Hoerhinweis aus dem transcript."
      },
      "statement_4": {
        "correct_answer": "false",
        "reason": "Kurze klare Begruendung, warum die Aussage stimmt oder nicht stimmt.",
        "evidence": "Der konkrete Hoerhinweis aus dem transcript."
      },
      "statement_5": {
        "correct_answer": "true",
        "reason": "Kurze klare Begruendung, warum die Aussage stimmt oder nicht stimmt.",
        "evidence": "Der konkrete Hoerhinweis aus dem transcript."
      }
    }
  }
}

WICHTIG:
- Gib genau 5 statements zurueck.
- Gib genau 5 correct-Werte zurueck.
- Gib genau 5 explanation-Objekte zurueck.
- Keine Dialoge.
- Keine zweite Stimme.
- Kein Markdown.
PROMPT;
    }

    private function buildListeningLongTrueFalsePrompt(string $difficulty, string $topicInstruction, string $retryHint = '', string $goldenExample = ''): string
    {
        $retrySection = $retryHint ? "\n{$retryHint}\n" : '';
        $goldenExampleSection = $goldenExample !== ''
            ? "\nStilorientierung (NUR fuer Ton und Struktur — NICHT inhaltlich kopieren):\n---\n{$goldenExample}\n---\n"
            : '';

        return <<<PROMPT
Du bist ein erfahrener telc-Pruefungsautor fuer Deutsch B2 Allgemein.
{$goldenExampleSection}
Erstelle eine Hören Teil 2 Uebungsaufgabe als Rundfunk-Interview mit genau 10 Aussagen richtig/falsch.
{$retrySection}

Verpflichtende Regeln:
- {$topicInstruction}
- Schwierigkeitsgrad: {$difficulty}
- Das Audio-Format ist ein Rundfunk-Interview (Moderator + Gast), kein Nachrichtensender und keine "fuenf kurzen Texte".
- Der transcript soll wie ein echtes Radio-Interview klingen: natuerliche Sprecherwechsel, klare Struktur, sachlicher Stil.
- Ziellaenge fuer den transcript: mindestens 320 Woerter.
- Das Interview muss inhaltlich genug Substanz liefern, damit 10 Aussagen eindeutig als true/false bewertet werden koennen.
- Genau 10 Aussagen mit IDs statement_1 bis statement_10.
- Jede Aussage muss eng am transcript haengen, darf aber nicht nur wortgleich kopieren.
- Jede Aussage muss eindeutig true oder false sein.
- context.speaker muss gesetzt sein (z.B. "Moderator und Expertin").
- context.replay_limit muss auf 1 gesetzt sein.
- audio_notes muessen klar sagen, dass es ein Rundfunk-Interview ist.
- Zu jeder Aussage braucht es eine explanation mit:
  - correct_answer
  - reason
  - evidence
- evidence muss sich auf eine konkrete Stelle im transcript beziehen.
- Keine Markdown-Ausgabe.

Antworte NUR mit einem JSON-Objekt in diesem Format:
{
  "topic": "Kurzer Titel des Interviews",
  "difficulty": "{$difficulty}",
  "content": {
    "format": "listening_long_true_false",
    "instructions": "Sie hören ein Interview. Entscheiden Sie, ob die Aussagen richtig oder falsch sind.",
    "audio": {
      "title": "Rundfunk-Interview: Thema",
      "audio_notes": "Rundfunk-Interview mit Moderator und Gast in ruhigem, klar verständlichem Sprechtempo."
    },
    "transcript": "Langer Interviewtext mit Sprecherwechseln.",
    "context": {
      "speaker": "Moderator und Gast",
      "replay_limit": 1
    },
    "statements": [
      { "id": "statement_1", "number": 1, "text": "Aussage 1" },
      { "id": "statement_2", "number": 2, "text": "Aussage 2" },
      { "id": "statement_3", "number": 3, "text": "Aussage 3" },
      { "id": "statement_4", "number": 4, "text": "Aussage 4" },
      { "id": "statement_5", "number": 5, "text": "Aussage 5" },
      { "id": "statement_6", "number": 6, "text": "Aussage 6" },
      { "id": "statement_7", "number": 7, "text": "Aussage 7" },
      { "id": "statement_8", "number": 8, "text": "Aussage 8" },
      { "id": "statement_9", "number": 9, "text": "Aussage 9" },
      { "id": "statement_10", "number": 10, "text": "Aussage 10" }
    ],
    "correct": {
      "statement_1": "true",
      "statement_2": "false",
      "statement_3": "true",
      "statement_4": "false",
      "statement_5": "true",
      "statement_6": "false",
      "statement_7": "true",
      "statement_8": "false",
      "statement_9": "true",
      "statement_10": "false"
    },
    "explanation": {
      "statement_1": {
        "correct_answer": "true",
        "reason": "Kurze klare Begruendung, warum die Aussage stimmt oder nicht stimmt.",
        "evidence": "Konkreter Hoerhinweis aus dem Transcript."
      },
      "statement_2": {
        "correct_answer": "false",
        "reason": "Kurze klare Begruendung, warum die Aussage stimmt oder nicht stimmt.",
        "evidence": "Konkreter Hoerhinweis aus dem Transcript."
      }
    }
  }
}

WICHTIG:
- Gib genau 10 statements zurueck.
- Gib genau 10 correct-Werte zurueck.
- Gib genau 10 explanation-Objekte zurueck.
- Halte Teil-2-Interview-Framing strikt ein.
PROMPT;
    }

    private function buildListeningSegmentedTrueFalsePrompt(string $difficulty, string $topicInstruction, string $retryHint = '', string $goldenExample = ''): string
    {
        $retrySection = $retryHint ? "\n{$retryHint}\n" : '';
        $goldenExampleSection = $goldenExample !== ''
            ? "\nStilorientierung (NUR fuer Teil-1-Struktur und Ton — NICHT inhaltlich kopieren):\n---\n{$goldenExample}\n---\n"
            : '';

        return <<<PROMPT
Du bist ein erfahrener telc-Pruefungsautor fuer Deutsch B2 Allgemein.
{$goldenExampleSection}
        Erstelle eine echte Hören Teil 1 Nachrichtensendung fuer B2 Allgemein.
{$retrySection}

Verpflichtende Regeln:
- {$topicInstruction}
- Schwierigkeitsgrad: {$difficulty}
- Das Ergebnis muss wie eine echte Nachrichtensendung mit einem Anchor-Intro und genau 5 klar getrennten Meldungen aufgebaut sein.
- Es gibt genau 1 Intro und genau 5 Segmente.
- Jede der 5 Aussagen gehoert genau zu EINEM Segment.
- Die finale Audio-Idee ist: Anchor-Intro + 5 vollwertige Meldungen.
- Der Stil muss sich an echten B2-Teil-1-Nachrichten orientieren: kompakt, sachlich, informativ, ohne Nebengeschichten.
- Jedes Segment soll wie eine einzelne Meldung aus einer Nachrichtensendung klingen und moeglichst mit einem Ortsmarker oder klaren Kontextanker starten, zum Beispiel: "Berlin.", "Bonn.", "Muenchen.", "Freiburg.", "Leipzig.".
- Bevorzuge Themen wie Stadtleben, Verkehr, Wetter, Veranstaltungen, Gesundheit, Gesellschaft, Forschung, Verbraucherhinweise oder offizielle Mitteilungen.
- Keine Dialoge, keine Interviews, keine Moderationsfragen, keine Service-Hotline-Rollen.
- Segmenttexte muessen sachlich, klar und fuer B2-Lernende gut verstaendlich sein.
- Das Intro soll kurz, aber vollwertig sein und etwa 25 bis 40 Woerter umfassen.
- Jedes der 5 Segmente soll etwa 55 bis 75 Woerter und in der Regel 2 bis 4 Saetze haben.
- Gesamtlaenge des finalen transcript: etwa 320 bis 420 Woerter.
- Die gesamte Nachrichtensendung soll sich beim Vorlesen wie ein echter Teil-1-Block von ungefaehr 2,5 bis 3 Minuten anfuehlen.
- VERMEIDE ultra-knappe Ein-Satz-Meldungen, Mikrotexte und jede Form von 40- bis 60-Sekunden-Kurzbulletin.
- VERMEIDE im Titel, in audio_notes und im Intro Formulierungen wie "kurz", "Kurzmeldung", "Kurznachrichten" oder "fuenf kurze Meldungen".
- Jedes Segment braucht einen voice_profile-Wert.
- Das Intro soll fuer v1 den voice_profile-Wert "anchor_main" haben.
- Alle 5 Segmente sollen fuer v1 denselben stabilen news voice verwenden: "news_main".
- audio_notes muessen beschreiben, dass dies eine Nachrichtensendung mit neutraler Sprecherstimme ist.
- Jede Aussage muss eindeutig true oder false sein.
- Fuer jedes Segment braucht es:
  - statement_id
  - statement_text
  - correct_answer
  - reason
  - evidence
- "evidence" muss eine kurze, hoerbare Stelle aus genau diesem Segment wiedergeben oder sehr nah paraphrasieren.

Antworte NUR mit einem JSON-Objekt in diesem Format:
{
  "topic": "Kurzer Teil-1-Titel",
  "difficulty": "{$difficulty}",
  "content": {
    "format": "listening_segmented_true_false",
    "instructions": "Sie hören nun eine Nachrichtensendung. Dazu sollen Sie fünf Aufgaben lösen. Sie hören die Nachrichtensendung nur einmal.",
    "audio": {
      "title": "Regionalnachrichten am Morgen",
      "audio_notes": "Nachrichtensendung mit neutraler Sprecherstimme und fünf klar getrennten, ausführlicheren Meldungen aus den Regionen."
    },
    "intro": {
      "text": "Guten Tag. Hier sind die Nachrichten aus den Regionen. Sie hören jetzt fünf Meldungen zu Verkehr, Alltag, Gesellschaft und Veranstaltungen.",
      "voice_profile": "anchor_main"
    },
    "segments": [
      {
        "id": "segment_1",
        "number": 1,
        "voice_profile": "news_main",
        "segment_text": "Berlin. Die Stadt erweitert ab kommender Woche ihr Angebot an mobilen Bürgerbüros. In mehreren Bezirken sollen zusätzliche Termine eingerichtet werden, damit Ausweise und Meldebescheinigungen schneller beantragt werden können.",
        "statement_id": "statement_1",
        "statement_text": "Aussage 1",
        "correct_answer": "true",
        "reason": "Kurze klare Begruendung.",
        "evidence": "Direkt hoerbarer Hinweis aus Segment 1."
      },
      {
        "id": "segment_2",
        "number": 2,
        "voice_profile": "news_main",
        "segment_text": "Bonn. Wegen umfangreicher Bauarbeiten wird die rechte Fahrspur auf der Stadtautobahn bis Freitag gesperrt. Pendler müssen vor allem in den Morgenstunden mit längeren Fahrzeiten rechnen und sollten mehr Zeit einplanen.",
        "statement_id": "statement_2",
        "statement_text": "Aussage 2",
        "correct_answer": "false",
        "reason": "Kurze klare Begruendung.",
        "evidence": "Direkt hoerbarer Hinweis aus Segment 2."
      },
      {
        "id": "segment_3",
        "number": 3,
        "voice_profile": "news_main",
        "segment_text": "Freiburg. Das Gesundheitsamt beginnt in Schulen und Sportvereinen eine neue Informationsreihe zum Schutz vor Zecken. Eltern erhalten Merkblätter, und Kinder sollen lernen, worauf sie nach Ausflügen in Parks und Wälder achten müssen.",
        "statement_id": "statement_3",
        "statement_text": "Aussage 3",
        "correct_answer": "true",
        "reason": "Kurze klare Begruendung.",
        "evidence": "Direkt hoerbarer Hinweis aus Segment 3."
      },
      {
        "id": "segment_4",
        "number": 4,
        "voice_profile": "news_main",
        "segment_text": "München. Ein Forschungsteam der Universität hat ein Sensorsystem vorgestellt, das Schäden auf Fahrradwegen früh erkennen soll. Die Stadt will das neue System zunächst auf zwei stark genutzten Strecken im Alltag testen.",
        "statement_id": "statement_4",
        "statement_text": "Aussage 4",
        "correct_answer": "false",
        "reason": "Kurze klare Begruendung.",
        "evidence": "Direkt hoerbarer Hinweis aus Segment 4."
      },
      {
        "id": "segment_5",
        "number": 5,
        "voice_profile": "news_main",
        "segment_text": "Dresden. Für das Kulturfestival am ersten Maiwochenende sind mehr Veranstaltungen geplant als im Vorjahr. Wer an den Führungen in kleineren Gruppen teilnehmen möchte, muss sich allerdings vorab online anmelden.",
        "statement_id": "statement_5",
        "statement_text": "Aussage 5",
        "correct_answer": "true",
        "reason": "Kurze klare Begruendung.",
        "evidence": "Direkt hoerbarer Hinweis aus Segment 5."
      }
    ]
  }
}

WICHTIG:
- Genau 5 Segmente.
- Genau 5 Statement-IDs.
- Keine zusaetzliche sechste Meldung.
- Kein Markdown.
- Keine zweite Struktur neben intro + segments.
PROMPT;
    }
}
