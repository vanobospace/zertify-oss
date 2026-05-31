<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ListeningDraftExplanationGenerationService
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<string, array<string, string>>
     */
    public function generate(array $content, string $topic = '', string $qualityRetryHint = ''): array
    {
        $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $topicLine = $topic !== '' ? "Thema/Titel der Aufgabe: {$topic}\n" : '';
        $retrySection = $qualityRetryHint !== '' ? "\n{$qualityRetryHint}\n" : '';

        $prompt = <<<PROMPT
Du bist ein erfahrener telc-Trainer fuer Deutsch B2 Allgemein.

Erzeuge NUR die fehlenden oder unvollstaendigen Erklaerungen fuer eine bestehende Hören-Aufgabe. Veraendere NICHT den Transcript, NICHT die Aussagen und NICHT die richtigen Antworten.
{$retrySection}

{$topicLine}Input-JSON:
{$json}

Gib NUR ein JSON-Objekt mit exakt denselben Statement-IDs wie in "correct" zurueck, zum Beispiel:
{
  "statement_1": {
    "correct_answer": "true",
    "reason": "Die Aussage ist richtig, weil im Audio klar gesagt wird, dass der Flohmarkt in die Markthalle verlegt wird.",
    "evidence": "in die Markthalle verlegt"
  }
}

Regeln:
- correct_answer muss exakt den Wert aus "correct" wiederholen, also true oder false.
- reason muss kurz und konkret erklaeren, warum die Aussage richtig oder falsch ist.
- evidence muss eine kurze, direkt hoerbare Textstelle aus dem Transcript zitieren oder sehr nah paraphrasieren.
- Erfinde keine zusaetzlichen Fakten.
- Stuetz dich nur auf den sichtbaren Transcript.
- Return ONLY valid JSON.
PROMPT;

        try {
            $response = Http::connectTimeout($this->connectTimeoutSeconds())
                ->timeout($this->requestTimeoutSeconds())
                ->post($this->endpoint(), [
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
                throw new RuntimeException('Listening explanation generation returned invalid JSON: '.$text);
            }

            return $this->normalizeExplanationMap($data);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Listening explanation generation timed out or could not connect: '.$e->getMessage(), previous: $e);
        } catch (RequestException $e) {
            throw new RuntimeException('Listening explanation generation API error: '.$e->getMessage(), previous: $e);
        }
    }

    private function endpoint(): string
    {
        $apiKey = trim((string) config('services.gemini.key', ''));
        $model = (string) config('services.gemini.model', 'gemini-3.1-flash-lite-preview');

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    }

    private function connectTimeoutSeconds(): int
    {
        return (int) config('services.gemini.connect_timeout_seconds', 10);
    }

    private function requestTimeoutSeconds(): int
    {
        return (int) config('services.gemini.explanation_timeout_seconds', 30);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, string>>
     */
    private function normalizeExplanationMap(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $explanation) {
            if (! is_array($explanation)) {
                continue;
            }

            $normalized[(string) $key] = [
                'correct_answer' => trim((string) ($explanation['correct_answer'] ?? '')),
                'reason' => trim((string) ($explanation['reason'] ?? '')),
                'evidence' => trim((string) ($explanation['evidence'] ?? '')),
            ];
        }

        return $normalized;
    }
}
