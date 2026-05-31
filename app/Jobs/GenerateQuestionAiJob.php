<?php

namespace App\Jobs;

use App\Models\Question;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateQuestionAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Позволяет задаче выполняться до 5 минут без таймаута очереди.
     */
    public $timeout = 300;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public Question $question,
        public array $options
    ) {}

    public function handle(GeminiService $gemini): void
    {
        try {
            $generated = $gemini->generateQuestion($this->options);

            $this->question->update([
                'status' => Question::STATUS_DRAFT,
                'generation_mode' => Question::GENERATION_MODE_AI_DRAFT,
                'difficulty' => $generated['difficulty'] ?? $this->options['difficulty'] ?? $this->question->difficulty,
                'topic' => $generated['topic'] ?? $this->options['topic_catalog_title'] ?? $this->question->topic,
                'content' => is_array($generated['content'] ?? null) ? $generated['content'] : [],
            ]);

        } catch (Throwable $e) {
            Log::error('AI Generation Job Failed: '.$e->getMessage(), ['exception' => $e]);

            $this->question->update([
                'status' => Question::STATUS_DRAFT,
                'generation_mode' => Question::GENERATION_MODE_MANUAL,
            ]);
        }
    }
}
