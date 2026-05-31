<?php

namespace App\Support;

use Throwable;

class AdminQuestionGenerationMessageTranslator
{
    public function generateActionLabel(): string
    {
        return __('admin.question_generation.generate_action');
    }

    public function regenerateActionLabel(): string
    {
        return __('admin.question_generation.regenerate_action');
    }

    public function regenerateHeading(): string
    {
        return __('admin.question_generation.regenerate_heading');
    }

    public function regenerateDescription(): string
    {
        return __('admin.question_generation.regenerate_description');
    }

    public function regenerateSubmitLabel(): string
    {
        return __('admin.question_generation.regenerate_submit');
    }

    public function moduleMissingTitle(): string
    {
        return __('admin.question_generation.module_missing_title');
    }

    public function moduleMissingBody(): string
    {
        return __('admin.question_generation.module_missing_body');
    }

    public function moduleNotFoundTitle(): string
    {
        return __('admin.question_generation.module_not_found_title');
    }

    public function moduleNotFoundBody(): string
    {
        return __('admin.question_generation.module_not_found_body');
    }

    public function generatedTitle(int $wordCount): string
    {
        return __('admin.question_generation.generated_title', ['count' => $wordCount]);
    }

    /**
     * @param  list<string>  $qualityWarnings
     * @param  list<string>  $reviewGapIds
     */
    public function generatedBody(string $themeTitle, array $qualityWarnings, array $reviewGapIds = []): string
    {
        $body = __('admin.question_generation.generated_body', ['theme' => $themeTitle]);

        if ($reviewGapIds !== []) {
            $gapNumbers = implode(', ', array_map(
                static fn (string $gapId): string => str_replace('gap_', '', $gapId),
                $reviewGapIds,
            ));

            return $body.' '.__('admin.question_generation.generated_review_gaps', [
                'gaps' => $gapNumbers,
            ]);
        }

        $translatedWarnings = array_slice($this->translateMessages($qualityWarnings), 0, 1);

        if ($translatedWarnings === []) {
            return $body;
        }

        return $body.' '.__('admin.question_generation.generated_warnings', [
            'warnings' => implode(' | ', $translatedWarnings),
        ]);
    }

    public function failedTitle(): string
    {
        return __('admin.question_generation.failed_title');
    }

    public function activationRequiresPassedExplanations(): string
    {
        return __('admin.question_generation.activation_requires_passed_explanations');
    }

    public function translateThrowable(Throwable $throwable): string
    {
        return $this->translateMessage($throwable->getMessage());
    }

    /**
     * @param  list<string>  $messages
     * @return list<string>
     */
    public function translateMessages(array $messages): array
    {
        return array_values(array_filter(array_map(
            fn (string $message): string => $this->translateMessage($message),
            $messages,
        )));
    }

    public function translateMessage(string $message): string
    {
        $message = trim($message);

        if ($message === '') {
            return '';
        }

        if (str_starts_with($message, 'Question generation failed quality checks: ')) {
            return __('admin.question_generation.failed_quality_checks', [
                'details' => $this->translatePipeSeparated(
                    substr($message, strlen('Question generation failed quality checks: ')),
                ),
            ]);
        }

        if (str_starts_with($message, 'Question generation exceeded the interactive time budget before another retry could start. ')) {
            return __('admin.question_generation.time_budget_before_retry', [
                'details' => $this->translatePipeSeparated(
                    substr($message, strlen('Question generation exceeded the interactive time budget before another retry could start. ')),
                ),
            ]);
        }

        if (str_starts_with($message, 'Question generation exceeded the interactive time budget while trying to improve explanations. ')) {
            return __('admin.question_generation.time_budget_explanations', [
                'details' => $this->translatePipeSeparated(
                    substr($message, strlen('Question generation exceeded the interactive time budget while trying to improve explanations. ')),
                ),
            ]);
        }

        if (str_starts_with($message, 'Gemini API error: ')) {
            return __('admin.question_generation.gemini_api_error', [
                'message' => substr($message, strlen('Gemini API error: ')),
            ]);
        }

        if (str_starts_with($message, 'Gemini returned invalid JSON structure: ')) {
            return __('admin.question_generation.invalid_json_structure');
        }

        if (str_starts_with($message, 'Gemini returned invalid JSON: ')) {
            return __('admin.question_generation.invalid_json');
        }

        if (str_starts_with($message, 'Translation returned invalid JSON: ')) {
            return __('admin.question_generation.invalid_translation_json');
        }

        if ($message === 'Question generation failed before validation.') {
            return __('admin.question_generation.failed_before_validation');
        }

        if ($message === 'Explanations need editorial review before publishing this question.') {
            return __('admin.question_generation.editorial_review');
        }

        if ($message === 'Per-gap text does not clearly show a conventional greeting and closing formula.') {
            return __('admin.question_generation.warnings.per_gap_email_markers');
        }

        $translatedMessage = $this->translatePatternMessage($message);

        return $translatedMessage ?? $message;
    }

    private function translatePipeSeparated(string $message): string
    {
        $parts = array_filter(array_map('trim', explode(' | ', $message)));

        return implode(' | ', array_map(
            fn (string $part): string => $this->translateMessage($part),
            $parts,
        ));
    }

    private function translatePatternMessage(string $message): ?string
    {
        $patterns = [
            '/^Text is too short \((\d+) words, expected at least (\d+)\)\.$/' => fn (array $matches): string => __('admin.question_generation.errors.text_too_short', [
                'count' => $matches[1],
                'min' => $matches[2],
            ]),
            '/^Shared-pool text should read like a multi-paragraph article with at least 3 paragraphs\.$/' => fn (): string => __('admin.question_generation.errors.shared_pool_paragraphs'),
            '/^Shared-pool text should not look like an email with greeting or closing formula\.$/' => fn (): string => __('admin.question_generation.errors.shared_pool_email_markers'),
            '/^Per-gap text should read like a complete email with at least 4 paragraphs\.$/' => fn (): string => __('admin.question_generation.errors.per_gap_paragraphs'),
            '/^Correct answers must contain exactly gap_1 to gap_10 in order\.$/' => fn (): string => __('admin.question_generation.errors.correct_gap_order'),
            '/^Shared-pool questions must mark content\.format as shared_pool\.$/' => fn (): string => __('admin.question_generation.errors.shared_pool_format'),
            '/^Shared-pool questions must include exactly 15 options in options_pool\.$/' => fn (): string => __('admin.question_generation.errors.shared_pool_pool_count'),
            '/^Shared-pool options must be unique\.$/' => fn (): string => __('admin.question_generation.errors.shared_pool_unique'),
            '/^Shared-pool options are missing the correct answer \'(.+)\'\.$/' => fn (array $matches): string => __('admin.question_generation.errors.shared_pool_missing_answer', [
                'answer' => $matches[1],
            ]),
            '/^Per-gap question (gap_\d+) must contain exactly 3 options\.$/' => fn (array $matches): string => __('admin.question_generation.errors.per_gap_option_count', [
                'gap' => $matches[1],
            ]),
            '/^Per-gap question (gap_\d+) contains duplicate options\.$/' => fn (array $matches): string => __('admin.question_generation.errors.per_gap_duplicate_options', [
                'gap' => $matches[1],
            ]),
            '/^Per-gap question (gap_\d+) is missing the correct option in its choices\.$/' => fn (array $matches): string => __('admin.question_generation.errors.per_gap_missing_correct', [
                'gap' => $matches[1],
            ]),
            '/^Text must contain exactly 10 gap markers\.$/' => fn (): string => __('admin.question_generation.errors.gap_marker_count'),
            '/^Generated question is missing content payload\.$/' => fn (): string => __('admin.question_generation.errors.missing_content'),
            '/^Generated question is missing structured explanations\.$/' => fn (): string => __('admin.question_generation.errors.missing_structured_explanations'),
            '/^Explanation for (gap_\d+) must be a structured object\.$/' => fn (array $matches): string => __('admin.question_generation.errors.structured_object', [
                'gap' => $matches[1],
            ]),
            '/^Explanation for (gap_\d+) must repeat the exact correct answer\.$/' => fn (array $matches): string => __('admin.question_generation.errors.repeat_exact_answer', [
                'gap' => $matches[1],
            ]),
            '/^Explanation for (gap_\d+) must use an allowed rule_type\.$/' => fn (array $matches): string => __('admin.question_generation.errors.allowed_rule_type', [
                'gap' => $matches[1],
            ]),
            '/^Explanation for (gap_\d+) needs a more concrete reason\.$/' => fn (array $matches): string => __('admin.question_generation.errors.concrete_reason', [
                'gap' => $matches[1],
            ]),
            '/^Explanation for (gap_\d+) must explain why a nearby distractor does not fit\.$/' => fn (array $matches): string => __('admin.question_generation.errors.nearby_distractor', [
                'gap' => $matches[1],
            ]),
            '/^Explanation for (gap_\d+) must name a concrete alternative, not a vague distractor\.$/' => fn (array $matches): string => __('admin.question_generation.errors.concrete_alternative', [
                'gap' => $matches[1],
            ]),
            '/^Explanation for (gap_\d+) needs a short transfer example\.$/' => fn (array $matches): string => __('admin.question_generation.errors.transfer_example', [
                'gap' => $matches[1],
            ]),
            '/^Explanation for (gap_\d+) example must show the same answer or construction explicitly\.$/' => fn (array $matches): string => __('admin.question_generation.errors.same_answer_or_pattern', [
                'gap' => $matches[1],
            ]),
            '/^Explanation for (gap_\d+) needs a concrete pattern or construction\.$/' => fn (array $matches): string => __('admin.question_generation.errors.concrete_pattern', [
                'gap' => $matches[1],
            ]),
            '/^Explanation for (gap_\d+) uses a rule_type that does not fit the actual answer\.$/' => fn (array $matches): string => __('admin.question_generation.errors.rule_type_mismatch', [
                'gap' => $matches[1],
            ]),
            '/^Gap (gap_\d+) explanation invents a hidden antecedent instead of explaining the visible sentence\.$/' => fn (array $matches): string => __('admin.question_generation.errors.hidden_antecedent', [
                'gap' => $matches[1],
            ]),
            '/^Gap (gap_\d+) uses a relative pronoun that does not match the visible antecedent in the sentence\.$/' => fn (array $matches): string => __('admin.question_generation.errors.visible_antecedent_mismatch', [
                'gap' => $matches[1],
            ]),
            '/^Gap (gap_\d+) produces an implausible relative clause in the local sentence\.$/' => fn (array $matches): string => __('admin.question_generation.errors.broken_relative_clause', [
                'gap' => $matches[1],
            ]),
        ];

        foreach ($patterns as $pattern => $resolver) {
            if (preg_match($pattern, $message, $matches) === 1) {
                return $resolver($matches);
            }
        }

        return null;
    }
}
