<?php

namespace App\Services;

use App\Support\ListeningTeilOneSegmentedContent;

class QuestionGenerationQualityValidator
{
    /**
     * @var list<string>
     */
    private const EMAIL_GREETING_MARKERS = [
        'hallo',
        'liebe',
        'lieber',
        'sehr geehrte',
        'sehr geehrter',
        'guten tag',
    ];

    /**
     * @var list<string>
     */
    private const EMAIL_CLOSING_MARKERS = [
        'viele gruesse',
        'viele grüße',
        'liebe gruesse',
        'liebe grüße',
        'beste gruesse',
        'beste grüße',
        'herzliche gruesse',
        'herzliche grüße',
        'freundliche gruesse',
        'freundliche grüße',
        'schoene gruesse',
        'schöne grüße',
        'mit freundlichen gruessen',
        'mit freundlichen grüßen',
        'danke im voraus',
        'ich freue mich auf ihre rueckmeldung',
        'ich freue mich auf ihre rückmeldung',
    ];

    /**
     * @var list<string>
     */
    private const ALLOWED_RULE_TYPES = [
        'Verb mit Präposition',
        'Präposition',
        'Konjunktion',
        'Doppelkonnektor',
        'Relativpronomen',
        'Relativadverb',
        'Pronominaladverb',
        'Modalverb',
        'Konjunktiv II',
        'Feste Wendung',
        'Feste Verbindung',
        'Nomenverbindung',
        'Adverb',
        'Kausaladverb',
        'Adversatives Adverb',
        'Additives Adverb',
        'Gradpartikel',
        'Kausalkonjunktion',
        'Konzessivkonjunktion',
        'Konsekutivkonjunktion',
        'Finalkonjunktion',
        'Interrogativadverb',
        'Fragepronomen',
        'Vergleichspartikel / Konnektor',
        'Artikel/Nomenphrase',
        'Grammatik',
    ];

    /**
     * @var array<string, string>
     */
    private const RULE_TYPE_SYNONYMS = [
        'verb mit praposition' => 'Verb mit Präposition',
        'verb mit präposition' => 'Verb mit Präposition',
        'praeposition' => 'Präposition',
        'präposition' => 'Präposition',
        'feste redewendung' => 'Feste Wendung',
        'redwendung' => 'Feste Wendung',
        'kollokation' => 'Feste Verbindung',
        'nomenphrase' => 'Artikel/Nomenphrase',
        'artikel/nomenphrase' => 'Artikel/Nomenphrase',
        'interrogativpronomen' => 'Fragepronomen',
        'vergleichskonjunktion' => 'Vergleichspartikel / Konnektor',
        'vergleichspartikel' => 'Vergleichspartikel / Konnektor',
        'konzessivadverb' => 'Adversatives Adverb',
        'temporalkonjunktion' => 'Konjunktion',
    ];

    /**
     * @var list<string>
     */
    private const PATTERN_REQUIRED_RULE_TYPES = [
        'Verb mit Präposition',
        'Doppelkonnektor',
        'Pronominaladverb',
        'Feste Wendung',
        'Feste Verbindung',
        'Nomenverbindung',
        'Finalkonjunktion',
        'Konsekutivkonjunktion',
        'Kausalkonjunktion',
        'Konzessivkonjunktion',
        'Konjunktiv II',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const ANSWER_RULE_TYPE_EXPECTATIONS = [
        'ob' => ['Konjunktion'],
        'dass' => ['Konjunktion'],
        'indem' => ['Konjunktion'],
        'während' => ['Konjunktion'],
        'waehrend' => ['Konjunktion'],
        'wobei' => ['Konjunktion', 'Relativadverb'],
        'wenn' => ['Konjunktion'],
        'falls' => ['Konjunktion'],
        'als' => ['Vergleichspartikel / Konnektor', 'Konjunktion'],
        'sodass' => ['Konsekutivkonjunktion', 'Konjunktion'],
        'so dass' => ['Konsekutivkonjunktion', 'Konjunktion'],
        'damit' => ['Finalkonjunktion', 'Konjunktion'],
        'denen' => ['Relativpronomen'],
        'dessen' => ['Relativpronomen'],
        'deren' => ['Relativpronomen'],
        'dem' => ['Relativpronomen', 'Präposition', 'Artikel/Nomenphrase'],
        'auf' => ['Präposition'],
        'mit' => ['Präposition'],
        'in' => ['Präposition'],
        'an' => ['Präposition'],
        'von' => ['Präposition'],
        'zu' => ['Präposition'],
        'bei' => ['Präposition'],
        'nach' => ['Präposition'],
    ];

    /**
     * @var list<string>
     */
    private const HIDDEN_ANTECEDENT_MARKERS = [
        'implizit',
        'gemeint',
        'faktor',
        'abstrakter begriff',
        'abstrakter begriff/faktor',
        'versteckt',
    ];

    /**
     * @return list<string>
     */
    public static function allowedRuleTypes(): array
    {
        return self::ALLOWED_RULE_TYPES;
    }

    /**
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
    public function validateQuestionContentPayload(array $content, string $format): array
    {
        if (in_array($format, ['per_gap', 'shared_pool'], true)) {
            return $this->validateGeneratedQuestion(['content' => $content], $format);
        }

        $errors = [];
        $warnings = [];
        $shouldRegenerateExplanations = false;
        $correct = is_array($content['correct'] ?? null) ? $content['correct'] : [];
        $explanations = is_array($content['explanation'] ?? null) ? $content['explanation'] : [];

        if (($content['format'] ?? null) !== $format) {
            $errors[] = "Content.format must be '{$format}'.";
        }

        if ($correct === []) {
            $errors[] = 'Correct answers payload is required.';
        }

        if ($format === 'reading_matching_headlines') {
            $texts = is_array($content['texts'] ?? null) ? $content['texts'] : [];
            $headings = is_array($content['headings'] ?? null) ? $content['headings'] : [];

            if (count($texts) !== 5) {
                $errors[] = 'Reading matching must contain exactly 5 texts.';
            }

            if (count($headings) !== 10) {
                $errors[] = 'Reading matching must contain exactly 10 headings.';
            }
        }

        if ($format === 'reading_article_mc') {
            $questions = is_array($content['questions'] ?? null) ? $content['questions'] : [];

            if (count($questions) !== 5) {
                $errors[] = 'Reading article MC must contain exactly 5 questions.';
            }

            foreach ($questions as $index => $question) {
                $options = is_array($question['options'] ?? null) ? $question['options'] : [];

                if (count($options) !== 3) {
                    $errors[] = 'Each reading article MC question must have exactly 3 options.';
                    $warnings[] = 'Broken question index: '.($index + 1).'.';
                }
            }
        }

        if ($format === 'reading_situations_matching') {
            $situations = is_array($content['situations'] ?? null) ? $content['situations'] : [];
            $texts = is_array($content['texts'] ?? null) ? $content['texts'] : [];

            if (count($situations) !== 10) {
                $errors[] = 'Reading situations matching must contain exactly 10 situations.';
            }

            if (count($texts) !== 12) {
                $errors[] = 'Reading situations matching must contain exactly 12 texts.';
            }

            if (! is_array($content['extra_answer'] ?? null)) {
                $errors[] = 'Reading situations matching must define extra_answer.';
            }
        }

        if (
            $format === ListeningTeilOneSegmentedContent::FORMAT
            || $format === 'listening_short_true_false'
            || $format === 'listening_long_true_false'
        ) {
            $statements = is_array($content['statements'] ?? null) ? $content['statements'] : [];
            $expectedCount = $format === 'listening_long_true_false' ? 10 : 5;

            if (count($statements) !== $expectedCount) {
                $errors[] = "Listening format {$format} must contain exactly {$expectedCount} statements.";
            }

            if (! is_array($content['audio'] ?? null)) {
                $errors[] = 'Listening content must include audio metadata.';
            } elseif (blank($content['audio']['title'] ?? null)) {
                $errors[] = 'Listening audio metadata must include a title.';
            }

            foreach ($correct as $answer) {
                if (! in_array($answer, ['true', 'false'], true)) {
                    $errors[] = 'Listening correct answers must use only true/false values.';
                    break;
                }
            }

            if ($format === ListeningTeilOneSegmentedContent::FORMAT) {
                $intro = is_array($content['intro'] ?? null) ? $content['intro'] : [];
                $segments = is_array($content['segments'] ?? null) ? $content['segments'] : [];

                if (blank($intro['text'] ?? null)) {
                    $errors[] = 'Segmented listening content must include an intro text.';
                }

                if (blank($intro['voice_profile'] ?? null)) {
                    $errors[] = 'Segmented listening content must include an intro voice profile.';
                }

                if (count($segments) !== 5) {
                    $errors[] = 'Segmented listening content must contain exactly 5 news segments.';
                }

                foreach ($segments as $index => $segment) {
                    if (! is_array($segment)) {
                        $errors[] = 'Each segmented listening item must be a structured segment object.';

                        continue;
                    }

                    if (blank($segment['voice_profile'] ?? null)) {
                        $errors[] = 'Each segmented listening item must include a voice profile.';
                    }

                    if (blank($segment['segment_text'] ?? null)) {
                        $errors[] = 'Each segmented listening item must include segment_text.';
                    }

                    if (blank($segment['statement_id'] ?? null) || blank($segment['statement_text'] ?? null)) {
                        $errors[] = 'Each segmented listening item must include statement mapping fields.';
                    }

                    if ((int) ($segment['number'] ?? 0) !== ($index + 1)) {
                        $warnings[] = 'Segment order should stay aligned with statement numbers.';
                    }
                }
            }
        }

        $missingExplanationIds = [];

        foreach (array_keys($correct) as $itemId) {
            if (! $this->hasQuestionExplanation($explanations[$itemId] ?? null, $format)) {
                $missingExplanationIds[] = $itemId;
            }
        }

        $explanationsStatus = $missingExplanationIds === [] ? 'passed' : 'failed';

        if ($missingExplanationIds !== []) {
            $errors[] = 'Missing explanations for: '.implode(', ', $missingExplanationIds).'.';
            $shouldRegenerateExplanations = true;
        }

        return [
            'passed' => $errors === [],
            'retryable' => false,
            'should_regenerate_explanations' => $shouldRegenerateExplanations,
            'errors' => $errors,
            'warnings' => $warnings,
            'review_gap_ids' => $missingExplanationIds,
            'explanations_status' => $explanationsStatus,
        ];
    }

    /**
     * @param  array<string, mixed>  $generated
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
    public function validateGeneratedQuestion(array $generated, string $format): array
    {
        $errors = [];
        $warnings = [];
        $retryable = false;
        $shouldRegenerateExplanations = false;
        $reviewGapIds = [];
        $hasStructuralGapContextErrors = false;

        $content = $generated['content'] ?? null;

        if (! is_array($content)) {
            return [
                'passed' => false,
                'retryable' => true,
                'should_regenerate_explanations' => false,
                'errors' => ['Generated question is missing content payload.'],
                'warnings' => [],
                'review_gap_ids' => [],
                'explanations_status' => 'failed',
            ];
        }

        $text = (string) ($content['text'] ?? '');
        $wordCount = GeminiService::countTextWords($text);
        $minWords = $format === 'shared_pool' ? 260 : 220;
        $maxWords = $format === 'shared_pool' ? 330 : 300;
        $paragraphs = preg_split('/\n\s*\n/u', trim($text)) ?: [];

        if ($wordCount < $minWords) {
            $errors[] = "Text is too short ({$wordCount} words, expected at least {$minWords}).";
            $retryable = true;
        }

        if ($wordCount > $maxWords) {
            $errors[] = "Text is too long ({$wordCount} words, expected at most {$maxWords}).";
            $retryable = true;
        }

        if ($format === 'shared_pool') {
            if (count($paragraphs) < 3) {
                $errors[] = 'Shared-pool text should read like a multi-paragraph article with at least 3 paragraphs.';
                $retryable = true;
            }

            if ($this->containsEmailMarkers($text)) {
                $errors[] = 'Shared-pool text should not look like an email with greeting or closing formula.';
                $retryable = true;
            }
        } else {
            if (count($paragraphs) < 4) {
                $errors[] = 'Per-gap text should read like a complete email with at least 4 paragraphs.';
                $retryable = true;
            }

            if (! $this->looksLikeEmail($paragraphs)) {
                $warnings[] = 'Per-gap text does not clearly show a conventional greeting and closing formula.';
            }
        }

        /** @var array<string, string> $correct */
        $correct = is_array($content['correct'] ?? null) ? $content['correct'] : [];
        $expectedGapIds = array_map(
            static fn (int $index): string => "gap_{$index}",
            range(1, 10),
        );

        if (array_values(array_keys($correct)) !== $expectedGapIds) {
            $errors[] = 'Correct answers must contain exactly gap_1 to gap_10 in order.';
            $retryable = true;
        }

        if ($format === 'shared_pool') {
            if (($content['format'] ?? null) !== 'shared_pool') {
                $errors[] = 'Shared-pool questions must mark content.format as shared_pool.';
                $retryable = true;
            }

            $pool = is_array($content['options_pool'] ?? null) ? $content['options_pool'] : [];

            if (count($pool) !== 15) {
                $errors[] = 'Shared-pool questions must include exactly 15 options in options_pool.';
                $retryable = true;
            }

            if (count(array_unique($pool)) !== count($pool)) {
                $errors[] = 'Shared-pool options must be unique.';
                $retryable = true;
            }

            foreach ($correct as $answer) {
                if (! $this->poolContainsAnswer($pool, $answer)) {
                    $errors[] = "Shared-pool options are missing the correct answer '{$answer}'.";
                    $retryable = true;
                }
            }

            $normalizedAnswers = array_map(fn (string $a): string => mb_strtolower(trim($a)), array_values($correct));

            if (count(array_unique($normalizedAnswers)) !== count($normalizedAnswers)) {
                $errors[] = 'Shared-pool correct answers must be unique — the same pool word cannot be assigned to more than one gap.';
                $retryable = true;
            }
        } else {
            $options = is_array($content['options'] ?? null) ? $content['options'] : [];

            foreach ($expectedGapIds as $gapId) {
                $gapOptions = $options[$gapId] ?? null;

                if (! is_array($gapOptions) || count($gapOptions) !== 3) {
                    $errors[] = "Per-gap question {$gapId} must contain exactly 3 options.";
                    $retryable = true;

                    continue;
                }

                if (count(array_unique($gapOptions)) !== count($gapOptions)) {
                    $errors[] = "Per-gap question {$gapId} contains duplicate options.";
                    $retryable = true;
                }

                if (isset($correct[$gapId]) && ! in_array($correct[$gapId], $gapOptions, true)) {
                    $errors[] = "Per-gap question {$gapId} is missing the correct option in its choices.";
                    $retryable = true;
                }
            }
        }

        $gapMarkersInText = preg_match_all('/\{\{gap_\d+\}\}/', $text, $matches);

        if ($gapMarkersInText !== 10) {
            $errors[] = 'Text must contain exactly 10 gap markers.';
            $retryable = true;
        }

        $explanations = $content['explanation'] ?? null;

        if (! is_array($explanations) || $explanations === []) {
            return [
                'passed' => false,
                'retryable' => $retryable,
                'should_regenerate_explanations' => true,
                'errors' => [...$errors, 'Generated question is missing structured explanations.'],
                'warnings' => $warnings,
                'review_gap_ids' => [],
                'explanations_status' => 'failed',
            ];
        }

        foreach ($expectedGapIds as $gapId) {
            $explanation = $explanations[$gapId] ?? null;
            $correctAnswer = (string) ($correct[$gapId] ?? '');

            if (! is_array($explanation)) {
                $errors[] = "Explanation for {$gapId} must be a structured object.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;

                continue;
            }

            $normalizedRuleType = $this->normalizeRuleType($this->stringValue($explanation['rule_type'] ?? ''));
            $reason = $this->stringValue($explanation['reason'] ?? '');
            $pattern = $this->stringValue($explanation['pattern'] ?? '');
            $contrast = $this->stringValue($explanation['contrast'] ?? '');
            $example = $this->stringValue($explanation['example'] ?? '');
            $answer = $this->stringValue($explanation['answer'] ?? '');
            $alternativeOptions = $this->extractAlternativeOptions($content, $gapId, $correctAnswer);

            if ($answer === '' || $answer !== $correctAnswer) {
                $errors[] = "Explanation for {$gapId} must repeat the exact correct answer.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;
            }

            if ($normalizedRuleType === '' || ! in_array($normalizedRuleType, self::ALLOWED_RULE_TYPES, true)) {
                $errors[] = "Explanation for {$gapId} must use an allowed rule_type.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;
            }

            if ($reason === '') {
                $errors[] = "Explanation for {$gapId} needs a more concrete reason.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;
            }

            if ($contrast === '') {
                $warnings[] = "Explanation for {$gapId} must explain why a nearby distractor does not fit.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;
            } elseif (! $this->contrastMentionsAlternative($contrast, $alternativeOptions)) {
                $warnings[] = "Explanation for {$gapId} must name a concrete alternative, not a vague distractor.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;
            }

            if ($example === '') {
                $warnings[] = "Explanation for {$gapId} needs a short transfer example.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;
            } elseif (! $this->exampleSupportsRule($example, $answer, $pattern)) {
                $warnings[] = "Explanation for {$gapId} example must show the same answer or construction explicitly.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;
            }

            if (
                in_array($normalizedRuleType, self::PATTERN_REQUIRED_RULE_TYPES, true)
                && $pattern === ''
            ) {
                $warnings[] = "Explanation for {$gapId} needs a concrete pattern or construction.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;
            }

            if (! $this->ruleTypeMatchesAnswer($answer, $normalizedRuleType)) {
                $warnings[] = "Explanation for {$gapId} uses a rule_type that does not fit the actual answer.";
                $shouldRegenerateExplanations = true;
                $reviewGapIds[] = $gapId;
            }

            $localContextError = $this->validateLocalGapContext(
                text: $text,
                gapId: $gapId,
                answer: $answer,
                ruleType: $normalizedRuleType,
                reason: $reason,
                contrast: $contrast,
            );

            if ($localContextError !== null) {
                $errors[] = $localContextError;
                $retryable = true;
                $hasStructuralGapContextErrors = true;
                $reviewGapIds[] = $gapId;
            }
        }

        if ($hasStructuralGapContextErrors) {
            $shouldRegenerateExplanations = false;
        }

        $errors = array_values(array_unique($errors));
        $warnings = array_values(array_unique($warnings));
        $reviewGapIds = array_values(array_unique($reviewGapIds));
        $explanationsStatus = $errors !== []
            ? 'failed'
            : ($reviewGapIds !== [] ? 'needs_review' : 'passed');

        return [
            'passed' => $errors === [],
            'retryable' => $retryable,
            'should_regenerate_explanations' => $shouldRegenerateExplanations,
            'errors' => $errors,
            'warnings' => $warnings,
            'review_gap_ids' => $reviewGapIds,
            'explanations_status' => $explanationsStatus,
        ];
    }

    /**
     * @param  array<string, mixed>  $generated
     * @return array<string, mixed>
     */
    public function normalizeGeneratedQuestion(array $generated): array
    {
        $content = $generated['content'] ?? null;

        if (! is_array($content)) {
            return $generated;
        }

        $generated['content'] = [
            ...$content,
            'text' => (string) ($content['text'] ?? ''),
            'correct' => $this->normalizeStringMap($content['correct'] ?? null),
            'options' => $this->normalizeNestedStringMap($content['options'] ?? null),
            'options_pool' => $this->normalizeStringList($content['options_pool'] ?? null),
        ];

        $content = $generated['content'];

        $explanations = $content['explanation'] ?? null;

        if (! is_array($explanations)) {
            return $generated;
        }

        $format = (string) ($content['format'] ?? '');

        $content['explanation'] = collect($explanations)
            ->map(function (mixed $explanation) use ($format): mixed {
                if (! is_array($explanation)) {
                    return $explanation;
                }

                if (in_array($format, [ListeningTeilOneSegmentedContent::FORMAT, 'listening_short_true_false', 'listening_long_true_false', 'reading_matching_headlines', 'reading_article_mc', 'reading_situations_matching'], true)) {
                    return [
                        'correct_answer' => $this->stringValue($explanation['correct_answer'] ?? ''),
                        'reason' => $this->stringValue($explanation['reason'] ?? ''),
                        'evidence' => $this->stringValue($explanation['evidence'] ?? ''),
                    ];
                }

                $ruleType = $this->normalizeRuleType($this->stringValue($explanation['rule_type'] ?? ''));

                return [
                    'answer' => $this->stringValue($explanation['answer'] ?? ''),
                    'rule_type' => $ruleType,
                    'reason' => $this->stringValue($explanation['reason'] ?? ''),
                    'pattern' => $this->stringValue($explanation['pattern'] ?? ''),
                    'contrast' => $this->stringValue($explanation['contrast'] ?? ''),
                    'example' => $this->stringValue($explanation['example'] ?? ''),
                ];
            })
            ->all();

        $generated['content'] = $content;

        return $generated;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeStringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[(string) $key] = $this->stringValue($item);
        }

        return $normalized;
    }

    /**
     * @return array<string, list<string>>
     */
    private function normalizeNestedStringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $key => $items) {
            $normalizedItems = [];

            if (is_array($items)) {
                foreach ($items as $item) {
                    $normalizedItems[] = $this->stringValue($item);
                }
            }

            $normalized[(string) $key] = $normalizedItems;
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $item): string => $this->stringValue($item),
            $value,
        ));
    }

    private function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_scalar($value) || $value === null) {
            return trim((string) $value);
        }

        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return is_string($encoded) ? trim($encoded) : '';
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return trim((string) $value);
        }

        return '';
    }

    /**
     * @param  array{
     *   errors: list<string>,
     *   warnings?: list<string>,
     *   review_gap_ids?: list<string>,
     *   should_regenerate_explanations: bool
     * }  $report
     */
    public function buildRetryHint(array $report): string
    {
        if ($report['should_regenerate_explanations']) {
            return 'WICHTIG: Die vorherigen Erklaerungen waren methodisch nicht stark genug. Fuer B2 Allgemein muss jede Luecke den exakten answer wiederholen, einen passenden rule_type haben und konkret erklaeren, warum genau diese Loesung in diesem Satz funktioniert. Bei Rektion, Verb+Praeposition, Doppelkonnektor und festen Wendungen MUSS pattern die Konstruktion nennen. contrast soll einen echten vorhandenen Distraktor benennen, example soll die Regel mit einem neuen kurzen deutschen Satz zeigen.';
        }

        $errors = is_array($report['errors'] ?? null) ? $report['errors'] : [];
        $instructions = [
            'Halte Format, Laenge, Lueckenstruktur und Antwortoptionen exakt ein.',
        ];

        foreach ($errors as $error) {
            if (! is_string($error) || trim($error) === '') {
                continue;
            }

            if (str_starts_with($error, 'Text is too short') || str_starts_with($error, 'Text is too long')) {
                preg_match('/Text is too (short|long) \((\d+) words, expected (at least|at most) (\d+)\)/', $error, $matches);
                $actualCount = isset($matches[2]) ? (int) $matches[2] : 0;
                $expectedMinMax = isset($matches[4]) ? (int) $matches[4] : 0;
                $isShort = ($matches[1] ?? '') === 'short';

                if ($actualCount > 0 && $expectedMinMax > 0) {
                    $reason = $isShort ? 'zu wenig' : 'zu viel';
                    $verb = $isShort ? 'mindestens' : 'hoechstens';
                    $instructions[] = "Der Text hatte {$actualCount} Woerter — das ist {$reason}. PFLICHT: Schreibe {$verb} {$expectedMinMax} Woerter netto. Passe die Textlaenge an (ohne die gap-Marker).";
                } else {
                    $instructions[] = 'Die Textlaenge war falsch. Schreibe fuer Teil 2 exakt im Bereich 260-330 Woerter netto und fuer Teil 1 im Bereich 220-300 Woerter netto.';
                }
            }

            if (preg_match('/^Listening (segmented|short|long) transcript is too (short|long) \((\d+) words, expected at least (\d+)\)\.?$/', $error, $matches) === 1) {
                $label = $matches[1] === 'long' ? 'Teil 2 Interview-Transcript' : ($matches[1] === 'segmented' ? 'Teil 1 Nachrichtensendung-Transcript' : 'Listening-Transcript');
                $actualCount = (int) $matches[3];
                $expectedMinimum = (int) $matches[4];
                $instructions[] = "{$label} hatte {$actualCount} Woerter. PFLICHT: Schreibe mindestens {$expectedMinimum} Woerter netto und liefere inhaltlich tragfaehige Aussagen ohne Wiederholung.";
            }

            if (str_starts_with($error, 'Shared-pool options are missing the correct answer')) {
                $instructions[] = 'Jede richtige Antwort aus "correct" MUSS woertlich und exakt einmal im "options_pool" stehen. Pruefe die Uebereinstimmung vor dem Zurueckgeben still selbst.';
            }

            if (str_starts_with($error, 'Shared-pool options must be unique')) {
                $instructions[] = 'Der options_pool enthaelt doppelte Eintraege — das ist VERBOTEN. Alle 15 Woerter im Pool MUESSEN verschieden sein. Pruefe vor der Ausgabe still: zaehle die Pool-Eintraege und stelle sicher, dass kein Wort zweimal vorkommt.';
            }

            if (str_starts_with($error, 'Shared-pool correct answers must be unique')) {
                $instructions[] = 'Ein Pool-Wort darf NUR EINMAL als richtige Antwort vergeben werden. Pruefe "correct": Kein Wort darf in zwei verschiedenen Luecken gleichzeitig die Loesung sein. Aendere den Text oder die Luecken so, dass jede richtige Antwort einzigartig ist.';
            }

            if (str_contains($error, 'relative pronoun') || str_contains($error, 'hidden antecedent')) {
                $instructions[] = 'Verwende Relativpronomen nur, wenn direkt links im sichtbaren Satz ein eindeutiges Bezugswort steht. Wenn das nicht sicher ist, verwende lieber Konjunktionen, Adverbien oder Pronominaladverbien.';
            }

            if (str_starts_with($error, 'Canonical Hören Teil 1 lock:')) {
                $instructions[] = 'Fuer hoeren-teil-1 gilt strikt: EIN Nachrichtensendung-Produkt mit intro + genau 5 Segmenten, statement_1..statement_5, intro voice anchor_main, Segment-Voices news_main, ohne Interview/Ansagen/fuenf kurze Texte.';
            }
        }

        return 'WICHTIG: Die vorherige Aufgabe hat die Modulregeln nicht sauber eingehalten. '.implode(' ', array_values(array_unique($instructions)));
    }

    private function normalizeRuleType(string $ruleType): string
    {
        $trimmed = trim($ruleType);

        if ($trimmed === '') {
            return '';
        }

        if (in_array($trimmed, self::ALLOWED_RULE_TYPES, true)) {
            return $trimmed;
        }

        $needle = mb_strtolower($trimmed);

        if (array_key_exists($needle, self::RULE_TYPE_SYNONYMS)) {
            return self::RULE_TYPE_SYNONYMS[$needle];
        }

        foreach (self::ALLOWED_RULE_TYPES as $allowedRuleType) {
            if (str_contains($needle, mb_strtolower($allowedRuleType))) {
                return $allowedRuleType;
            }
        }

        if (str_contains($needle, 'relativpron')) {
            return 'Relativpronomen';
        }

        if (str_contains($needle, 'relativadverb')) {
            return 'Relativadverb';
        }

        if (str_contains($needle, 'pronominaladverb')) {
            return 'Pronominaladverb';
        }

        if (str_contains($needle, 'doppelkonnektor')) {
            return 'Doppelkonnektor';
        }

        if (str_contains($needle, 'finalkonjunktion')) {
            return 'Finalkonjunktion';
        }

        if (str_contains($needle, 'konsekutivkonjunktion')) {
            return 'Konsekutivkonjunktion';
        }

        if (str_contains($needle, 'konzessivkonjunktion')) {
            return 'Konzessivkonjunktion';
        }

        if (str_contains($needle, 'kausalkonjunktion')) {
            return 'Kausalkonjunktion';
        }

        if (str_contains($needle, 'kausaladverb')) {
            return 'Kausaladverb';
        }

        if (str_contains($needle, 'fragepronomen') || str_contains($needle, 'interrogativpronomen')) {
            return 'Fragepronomen';
        }

        if (str_contains($needle, 'interrogativadverb')) {
            return 'Interrogativadverb';
        }

        if (str_contains($needle, 'vergleich') && (str_contains($needle, 'partikel') || str_contains($needle, 'konnektor') || str_contains($needle, 'konjunktion'))) {
            return 'Vergleichspartikel / Konnektor';
        }

        return $trimmed;
    }

    /**
     * @param  list<string>  $pool
     */
    private function poolContainsAnswer(array $pool, string $answer): bool
    {
        $normalizedAnswer = mb_strtolower(trim($answer));

        foreach ($pool as $option) {
            if (mb_strtolower(trim($option)) === $normalizedAnswer) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return list<string>
     */
    private function extractAlternativeOptions(array $content, string $gapId, string $correctAnswer): array
    {
        $alternatives = [];

        if (is_array($content['options'][$gapId] ?? null)) {
            /** @var list<string> $options */
            $options = $content['options'][$gapId];
            $alternatives = array_values(array_filter(
                $options,
                static fn (string $option): bool => $option !== $correctAnswer,
            ));
        } elseif (is_array($content['options_pool'] ?? null)) {
            /** @var list<string> $pool */
            $pool = $content['options_pool'];
            $alternatives = array_values(array_filter(
                $pool,
                static fn (string $option): bool => $option !== $correctAnswer,
            ));
        }

        return $alternatives;
    }

    /**
     * @param  list<string>  $alternatives
     */
    private function contrastMentionsAlternative(string $contrast, array $alternatives): bool
    {
        $normalizedContrast = mb_strtolower($contrast);

        foreach ($alternatives as $alternative) {
            if ($this->textContainsAnswer($normalizedContrast, $alternative)) {
                return true;
            }
        }

        return false;
    }

    private function textContainsAnswer(string $text, string $answer): bool
    {
        $normalizedText = mb_strtolower($text);
        $normalizedAnswer = preg_quote(mb_strtolower($answer), '/');

        return (bool) preg_match('/(^|[^\p{L}])'.$normalizedAnswer.'([^\p{L}]|$)/u', $normalizedText);
    }

    private function exampleSupportsRule(string $example, string $answer, string $pattern): bool
    {
        if ($this->textContainsAnswer($example, $answer)) {
            return true;
        }

        $patternTokens = $this->normalizePatternForExampleMatch($pattern);

        if ($patternTokens === []) {
            return false;
        }

        $matchedTokens = 0;

        foreach ($patternTokens as $token) {
            if ($this->textContainsAnswer($example, $token)) {
                $matchedTokens++;
            }
        }

        return $matchedTokens >= min(2, count($patternTokens));
    }

    /**
     * @return list<string>
     */
    private function normalizePatternForExampleMatch(string $pattern): array
    {
        $normalized = mb_strtolower($pattern);
        $normalized = preg_replace('/\+\s*[a-zäöü.]+/iu', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^\p{L}\s]/u', ' ', $normalized) ?? $normalized;
        $tokens = preg_split('/\s+/u', trim($normalized)) ?: [];

        return array_values(array_filter(
            $tokens,
            static fn (string $token): bool => mb_strlen($token) >= 3,
        ));
    }

    private function ruleTypeMatchesAnswer(string $answer, string $ruleType): bool
    {
        $normalizedAnswer = mb_strtolower(trim($answer));

        if (! array_key_exists($normalizedAnswer, self::ANSWER_RULE_TYPE_EXPECTATIONS)) {
            return true;
        }

        return in_array($ruleType, self::ANSWER_RULE_TYPE_EXPECTATIONS[$normalizedAnswer], true);
    }

    /**
     * @param  list<string>  $paragraphs
     */
    private function looksLikeEmail(array $paragraphs): bool
    {
        if ($paragraphs === []) {
            return false;
        }

        $firstParagraph = mb_strtolower($paragraphs[0]);
        $lastParagraph = mb_strtolower($paragraphs[array_key_last($paragraphs)]);
        $greetingFound = $this->containsAny($firstParagraph, self::EMAIL_GREETING_MARKERS);
        $closingFound = $this->containsAny($lastParagraph, self::EMAIL_CLOSING_MARKERS);

        return $greetingFound && $closingFound;
    }

    private function containsEmailMarkers(string $text): bool
    {
        $lowerText = mb_strtolower($text);

        return $this->containsAny($lowerText, [...self::EMAIL_GREETING_MARKERS, ...self::EMAIL_CLOSING_MARKERS]);
    }

    /**
     * @param  list<string>  $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function validateLocalGapContext(
        string $text,
        string $gapId,
        string $answer,
        string $ruleType,
        string $reason,
        string $contrast,
    ): ?string {
        $sentence = $this->extractSentenceForGap($text, $gapId);

        if ($sentence === null) {
            return null;
        }

        $filledSentence = str_replace('{{'.$gapId.'}}', $answer, $sentence);

        if (
            ($ruleType === 'Relativpronomen' || in_array(mb_strtolower($answer), ['der', 'die', 'das', 'denen', 'dessen', 'deren'], true))
            && $this->explanationInventsHiddenAntecedent($filledSentence, $reason, $contrast)
        ) {
            return "Gap {$gapId} explanation invents a hidden antecedent instead of explaining the visible sentence.";
        }

        if (
            ($ruleType === 'Relativpronomen' || in_array(mb_strtolower($answer), ['der', 'die', 'das'], true))
            && ! $this->relativePronounMatchesVisibleAntecedent($sentence, $gapId, $answer)
        ) {
            return "Gap {$gapId} uses a relative pronoun that does not match the visible antecedent in the sentence.";
        }

        if (
            ($ruleType === 'Relativpronomen' || in_array(mb_strtolower($answer), ['der', 'die', 'das'], true))
            && $this->looksLikeBrokenPassiveRelativeClause($filledSentence)
        ) {
            return "Gap {$gapId} produces an implausible relative clause in the local sentence.";
        }

        return null;
    }

    private function extractSentenceForGap(string $text, string $gapId): ?string
    {
        $marker = '{{'.$gapId.'}}';
        $position = strpos($text, $marker);

        if ($position === false) {
            return null;
        }

        $start = max(
            strrpos(substr($text, 0, $position), '.'),
            strrpos(substr($text, 0, $position), '!'),
            strrpos(substr($text, 0, $position), '?'),
            strrpos(substr($text, 0, $position), "\n"),
        );
        $start = $start === false ? 0 : $start + 1;

        $remainingText = substr($text, $position);
        $relativeEndPositions = array_filter([
            strpos($remainingText, '.'),
            strpos($remainingText, '!'),
            strpos($remainingText, '?'),
            strpos($remainingText, "\n"),
        ], static fn (int|false $value): bool => $value !== false);

        $end = $relativeEndPositions === []
            ? strlen($text)
            : $position + min($relativeEndPositions);

        return trim(substr($text, $start, $end - $start));
    }

    private function explanationInventsHiddenAntecedent(string $sentence, string $reason, string $contrast): bool
    {
        $sentenceLower = mb_strtolower($sentence);
        $combinedExplanation = mb_strtolower($reason.' '.$contrast);

        if (! $this->containsAny($combinedExplanation, self::HIDDEN_ANTECEDENT_MARKERS)) {
            return false;
        }

        return ! $this->containsAny($sentenceLower, self::HIDDEN_ANTECEDENT_MARKERS);
    }

    private function relativePronounMatchesVisibleAntecedent(string $sentence, string $gapId, string $answer): bool
    {
        [$leftContext] = explode('{{'.$gapId.'}}', $sentence, 2);
        $leftContext = trim($leftContext);

        if (
            preg_match(
                '/\b(die|der|das|eine|ein)\s+(?:[a-zäöüß-]+\s+){0,3}[A-ZÄÖÜ][\p{L}-]+\s*,?\s*$/u',
                $leftContext,
                $matches,
            ) !== 1
        ) {
            return true;
        }

        $article = mb_strtolower($matches[1]);
        $normalizedAnswer = mb_strtolower($answer);

        $expectedAnswers = match ($article) {
            'die', 'eine' => ['die', 'deren', 'denen'],
            'der', 'ein' => ['der', 'dessen', 'dem', 'denen'],
            'das' => ['das', 'dessen', 'dem'],
            default => [],
        };

        if ($expectedAnswers === []) {
            return true;
        }

        return in_array($normalizedAnswer, $expectedAnswers, true);
    }

    private function looksLikeBrokenPassiveRelativeClause(string $sentence): bool
    {
        $normalized = mb_strtolower($sentence);

        return preg_match(
            '/\b(die|der|das)\s+wir\b.*\b(versprochen|angeboten|mitgeteilt|geschickt|erklärt)\b.*\b(wurde|wurden)\b/u',
            $normalized,
        ) === 1;
    }

    private function hasQuestionExplanation(mixed $explanation, string $format): bool
    {
        if (! is_array($explanation)) {
            return false;
        }

        if (in_array($format, ['per_gap', 'shared_pool'], true)) {
            return isset($explanation['answer'], $explanation['rule_type'], $explanation['reason']);
        }

        return filled($explanation['correct_answer'] ?? null)
            && filled($explanation['reason'] ?? null);
    }
}
