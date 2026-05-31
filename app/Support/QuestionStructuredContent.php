<?php

namespace App\Support;

class QuestionStructuredContent
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function toStructured(array $content, ?string $format = null): array
    {
        $format ??= is_string($content['format'] ?? null) ? $content['format'] : null;

        if ($format === 'reading_matching_headlines') {
            return self::readingMatchingToStructured($content);
        }

        if ($format === 'reading_article_mc') {
            return self::readingArticleToStructured($content);
        }

        if ($format === 'reading_situations_matching') {
            return self::readingSituationsToStructured($content);
        }

        if ($format === ListeningTeilOneSegmentedContent::FORMAT) {
            return ListeningTeilOneSegmentedContent::toStructured($content);
        }

        if ($format === 'listening_short_true_false' || $format === 'listening_long_true_false') {
            return self::listeningToStructured($content, $format);
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $existingContent
     * @param  array<string, mixed>  $structured
     * @return array<string, mixed>
     */
    public static function mergeIntoContent(array $existingContent, array $structured, string $format): array
    {
        if ($format === 'reading_matching_headlines') {
            return self::mergeReadingMatching($existingContent, $structured);
        }

        if ($format === 'reading_article_mc') {
            return self::mergeReadingArticle($existingContent, $structured);
        }

        if ($format === 'reading_situations_matching') {
            return self::mergeReadingSituations($existingContent, $structured);
        }

        if ($format === ListeningTeilOneSegmentedContent::FORMAT) {
            return ListeningTeilOneSegmentedContent::mergeStructured($existingContent, $structured);
        }

        if ($format === 'listening_short_true_false' || $format === 'listening_long_true_false') {
            return self::mergeListening($existingContent, $structured, $format);
        }

        return $existingContent;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function readingMatchingToStructured(array $content): array
    {
        $correct = self::mapOfStrings($content['correct'] ?? null);
        $explanations = self::mapOfArrays($content['explanation'] ?? null);

        return [
            'instructions' => self::stringValue($content['instructions'] ?? null),
            'headings' => array_map(
                static fn (array $heading): array => [
                    'id' => self::stringValue($heading['id'] ?? null),
                    'label' => self::stringValue($heading['label'] ?? null),
                    'text' => self::stringValue($heading['text'] ?? null),
                ],
                self::listOfArrays($content['headings'] ?? null),
            ),
            'texts' => array_map(
                static fn (array $text): array => [
                    'id' => self::stringValue($text['id'] ?? null),
                    'title' => self::stringValue($text['title'] ?? null),
                    'body' => self::stringValue($text['body'] ?? null),
                    'correct_answer' => $correct[self::stringValue($text['id'] ?? null)] ?? '',
                    ...self::explanationFields($explanations[self::stringValue($text['id'] ?? null)] ?? null),
                ],
                self::listOfArrays($content['texts'] ?? null),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function readingArticleToStructured(array $content): array
    {
        $correct = self::mapOfStrings($content['correct'] ?? null);
        $explanations = self::mapOfArrays($content['explanation'] ?? null);
        $article = is_array($content['article'] ?? null) ? $content['article'] : [];

        return [
            'instructions' => self::stringValue($content['instructions'] ?? null),
            'article_title' => self::stringValue($article['title'] ?? null),
            'article_body' => self::stringValue($article['body'] ?? null),
            'questions' => array_map(
                static fn (array $question): array => [
                    'id' => self::stringValue($question['id'] ?? null),
                    'prompt' => self::stringValue($question['prompt'] ?? null),
                    'correct_answer' => $correct[self::stringValue($question['id'] ?? null)] ?? '',
                    ...self::explanationFields($explanations[self::stringValue($question['id'] ?? null)] ?? null),
                    'options' => array_map(
                        static fn (array $option): array => [
                            'id' => self::stringValue($option['id'] ?? null),
                            'label' => self::stringValue($option['label'] ?? null),
                            'text' => self::stringValue($option['text'] ?? null),
                        ],
                        self::listOfArrays($question['options'] ?? null),
                    ),
                ],
                self::listOfArrays($content['questions'] ?? null),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function readingSituationsToStructured(array $content): array
    {
        $correct = self::mapOfStrings($content['correct'] ?? null);
        $explanations = self::mapOfArrays($content['explanation'] ?? null);
        $extraAnswer = is_array($content['extra_answer'] ?? null) ? $content['extra_answer'] : [];

        return [
            'instructions' => self::stringValue($content['instructions'] ?? null),
            'situations' => array_map(
                static fn (array $situation): array => [
                    'id' => self::stringValue($situation['id'] ?? null),
                    'number' => self::intValue($situation['number'] ?? null),
                    'text' => self::stringValue($situation['text'] ?? null),
                    'correct_answer' => $correct[self::stringValue($situation['id'] ?? null)] ?? '',
                    ...self::explanationFields($explanations[self::stringValue($situation['id'] ?? null)] ?? null),
                ],
                self::listOfArrays($content['situations'] ?? null),
            ),
            'texts' => array_map(
                static fn (array $text): array => [
                    'id' => self::stringValue($text['id'] ?? null),
                    'label' => self::stringValue($text['label'] ?? null),
                    'title' => self::stringValue($text['title'] ?? null),
                    'body' => self::stringValue($text['body'] ?? null),
                ],
                self::listOfArrays($content['texts'] ?? null),
            ),
            'extra_answer_id' => self::stringValue($extraAnswer['id'] ?? null),
            'extra_answer_label' => self::stringValue($extraAnswer['label'] ?? null),
            'extra_answer_text' => self::stringValue($extraAnswer['text'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function listeningToStructured(array $content, string $format): array
    {
        $correct = self::mapOfStrings($content['correct'] ?? null);
        $explanations = self::mapOfArrays($content['explanation'] ?? null);
        $audio = is_array($content['audio'] ?? null) ? $content['audio'] : [];
        $context = is_array($content['context'] ?? null) ? $content['context'] : [];

        return [
            'instructions' => self::stringValue($content['instructions'] ?? null),
            'audio_title' => self::stringValue($audio['title'] ?? null),
            'audio_notes' => self::stringValue($audio['audio_notes'] ?? null),
            'transcript' => self::stringValue($content['transcript'] ?? null),
            'speaker' => $format === 'listening_long_true_false' ? self::stringValue($context['speaker'] ?? null) : '',
            'replay_limit' => $format === 'listening_long_true_false' ? self::nullableIntValue($context['replay_limit'] ?? null) : null,
            'statements' => array_map(
                static fn (array $statement): array => [
                    'id' => self::stringValue($statement['id'] ?? null),
                    'number' => self::intValue($statement['number'] ?? null),
                    'text' => self::stringValue($statement['text'] ?? null),
                    'correct_answer' => $correct[self::stringValue($statement['id'] ?? null)] ?? '',
                    ...self::explanationFields($explanations[self::stringValue($statement['id'] ?? null)] ?? null),
                ],
                self::listOfArrays($content['statements'] ?? null),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $existingContent
     * @param  array<string, mixed>  $structured
     * @return array<string, mixed>
     */
    private static function mergeReadingMatching(array $existingContent, array $structured): array
    {
        $headings = array_values(array_filter(array_map(
            static fn (array $heading): ?array => self::filledArray([
                'id' => self::stringValue($heading['id'] ?? null),
                'label' => self::stringValue($heading['label'] ?? null),
                'text' => self::stringValue($heading['text'] ?? null),
            ], ['id', 'text']),
            self::listOfArrays($structured['headings'] ?? null),
        )));

        $texts = [];
        $correct = [];
        $explanations = [];

        foreach (self::listOfArrays($structured['texts'] ?? null) as $text) {
            $item = self::filledArray([
                'id' => self::stringValue($text['id'] ?? null),
                'title' => self::stringValue($text['title'] ?? null),
                'body' => self::stringValue($text['body'] ?? null),
            ], ['id', 'body']);

            if ($item === null) {
                continue;
            }

            $texts[] = $item;
            $textId = $item['id'];
            $correct[$textId] = self::stringValue($text['correct_answer'] ?? null);
            $explanations[$textId] = self::buildExplanationDetails($text);
        }

        return array_merge($existingContent, [
            'format' => 'reading_matching_headlines',
            'instructions' => self::stringValue($structured['instructions'] ?? null),
            'headings' => $headings,
            'texts' => $texts,
            'correct' => $correct,
            'explanation' => $explanations,
        ]);
    }

    /**
     * @param  array<string, mixed>  $existingContent
     * @param  array<string, mixed>  $structured
     * @return array<string, mixed>
     */
    private static function mergeReadingArticle(array $existingContent, array $structured): array
    {
        $questions = [];
        $correct = [];
        $explanations = [];

        foreach (self::listOfArrays($structured['questions'] ?? null) as $question) {
            $questionId = self::stringValue($question['id'] ?? null);
            $prompt = self::stringValue($question['prompt'] ?? null);

            if ($questionId === '' || $prompt === '') {
                continue;
            }

            $options = array_values(array_filter(array_map(
                static fn (array $option): ?array => self::filledArray([
                    'id' => self::stringValue($option['id'] ?? null),
                    'label' => self::stringValue($option['label'] ?? null),
                    'text' => self::stringValue($option['text'] ?? null),
                ], ['id', 'text']),
                self::listOfArrays($question['options'] ?? null),
            )));

            $questions[] = [
                'id' => $questionId,
                'prompt' => $prompt,
                'options' => $options,
            ];
            $correct[$questionId] = self::stringValue($question['correct_answer'] ?? null);
            $explanations[$questionId] = self::buildExplanationDetails($question);
        }

        return array_merge($existingContent, [
            'format' => 'reading_article_mc',
            'instructions' => self::stringValue($structured['instructions'] ?? null),
            'article' => [
                'title' => self::stringValue($structured['article_title'] ?? null),
                'body' => self::stringValue($structured['article_body'] ?? null),
            ],
            'questions' => $questions,
            'correct' => $correct,
            'explanation' => $explanations,
        ]);
    }

    /**
     * @param  array<string, mixed>  $existingContent
     * @param  array<string, mixed>  $structured
     * @return array<string, mixed>
     */
    private static function mergeReadingSituations(array $existingContent, array $structured): array
    {
        $situations = [];
        $correct = [];
        $explanations = [];

        foreach (self::listOfArrays($structured['situations'] ?? null) as $situation) {
            $situationId = self::stringValue($situation['id'] ?? null);
            $text = self::stringValue($situation['text'] ?? null);

            if ($situationId === '' || $text === '') {
                continue;
            }

            $situations[] = [
                'id' => $situationId,
                'number' => self::intValue($situation['number'] ?? null),
                'text' => $text,
            ];
            $correct[$situationId] = self::stringValue($situation['correct_answer'] ?? null);
            $explanations[$situationId] = self::buildExplanationDetails($situation);
        }

        $texts = array_values(array_filter(array_map(
            static fn (array $text): ?array => self::filledArray([
                'id' => self::stringValue($text['id'] ?? null),
                'label' => self::stringValue($text['label'] ?? null),
                'title' => self::stringValue($text['title'] ?? null),
                'body' => self::stringValue($text['body'] ?? null),
            ], ['id', 'body']),
            self::listOfArrays($structured['texts'] ?? null),
        )));

        return array_merge($existingContent, [
            'format' => 'reading_situations_matching',
            'instructions' => self::stringValue($structured['instructions'] ?? null),
            'situations' => $situations,
            'texts' => $texts,
            'extra_answer' => [
                'id' => self::stringValue($structured['extra_answer_id'] ?? null),
                'label' => self::stringValue($structured['extra_answer_label'] ?? null),
                'text' => self::stringValue($structured['extra_answer_text'] ?? null),
            ],
            'correct' => $correct,
            'explanation' => $explanations,
        ]);
    }

    /**
     * @param  array<string, mixed>  $existingContent
     * @param  array<string, mixed>  $structured
     * @return array<string, mixed>
     */
    private static function mergeListening(array $existingContent, array $structured, string $format): array
    {
        $statements = [];
        $correct = [];
        $explanations = [];

        foreach (self::listOfArrays($structured['statements'] ?? null) as $statement) {
            $statementId = self::stringValue($statement['id'] ?? null);
            $text = self::stringValue($statement['text'] ?? null);

            if ($statementId === '' || $text === '') {
                continue;
            }

            $statements[] = [
                'id' => $statementId,
                'number' => self::intValue($statement['number'] ?? null),
                'text' => $text,
            ];
            $correct[$statementId] = self::stringValue($statement['correct_answer'] ?? null);
            $explanations[$statementId] = self::buildExplanationDetails($statement);
        }

        $content = array_merge($existingContent, [
            'format' => $format,
            'instructions' => self::stringValue($structured['instructions'] ?? null),
            'audio' => array_merge(
                is_array($existingContent['audio'] ?? null) ? $existingContent['audio'] : [],
                [
                    'title' => self::stringValue($structured['audio_title'] ?? null),
                    'audio_notes' => self::stringValue($structured['audio_notes'] ?? null),
                ],
            ),
            'transcript' => self::stringValue($structured['transcript'] ?? null),
            'statements' => $statements,
            'correct' => $correct,
            'explanation' => $explanations,
        ]);

        if ($format === 'listening_long_true_false') {
            $context = [
                'speaker' => self::stringValue($structured['speaker'] ?? null),
                'replay_limit' => self::nullableIntValue($structured['replay_limit'] ?? null),
            ];

            $content['context'] = array_filter($context, static fn (mixed $value): bool => $value !== null && $value !== '');
        } else {
            unset($content['context']);
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>|null  $details
     * @return array<string, string>
     */
    private static function explanationFields(?array $details): array
    {
        return [
            'reason' => self::stringValue($details['reason'] ?? null),
            'evidence' => self::stringValue($details['evidence'] ?? null),
            'wrong_answer_reason' => self::stringValue($details['wrong_answer_reason'] ?? null),
            'strategy_hint' => self::stringValue($details['strategy_hint'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, string>
     */
    private static function buildExplanationDetails(array $item): array
    {
        return [
            'correct_answer' => self::stringValue($item['correct_answer'] ?? null),
            'reason' => self::stringValue($item['reason'] ?? null),
            'evidence' => self::stringValue($item['evidence'] ?? null),
            'wrong_answer_reason' => self::stringValue($item['wrong_answer_reason'] ?? null),
            'strategy_hint' => self::stringValue($item['strategy_hint'] ?? null),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function listOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_array($item)));
    }

    /**
     * @return array<string, string>
     */
    private static function mapOfStrings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $mapped = [];

        foreach ($value as $key => $item) {
            if (! is_string($key)) {
                continue;
            }

            $mapped[$key] = self::stringValue($item);
        }

        return $mapped;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function mapOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $mapped = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && is_array($item)) {
                $mapped[$key] = $item;
            }
        }

        return $mapped;
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

    private static function intValue(mixed $value): int
    {
        return max(1, (int) $value);
    }

    private static function nullableIntValue(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(1, (int) $value);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $requiredKeys
     * @return array<string, mixed>|null
     */
    private static function filledArray(array $item, array $requiredKeys): ?array
    {
        foreach ($requiredKeys as $requiredKey) {
            if (self::stringValue($item[$requiredKey] ?? null) === '') {
                return null;
            }
        }

        return $item;
    }
}
