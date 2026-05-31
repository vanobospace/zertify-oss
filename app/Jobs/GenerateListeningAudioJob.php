<?php

namespace App\Jobs;

use App\Models\Question;
use App\Services\ListeningQuestionAudioSynthesisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateListeningAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(
        public Question $question,
        public string $previousGenerationMode = Question::GENERATION_MODE_MANUAL,
    ) {}

    public function handle(ListeningQuestionAudioSynthesisService $synthesisService): void
    {
        try {
            $freshQuestion = $this->question->fresh();

            if (! $freshQuestion instanceof Question) {
                return;
            }

            $synthesisService->synthesizeForQuestion($freshQuestion);

            $freshQuestion->forceFill([
                'generation_mode' => $this->previousGenerationMode !== ''
                    ? $this->previousGenerationMode
                    : Question::GENERATION_MODE_MANUAL,
            ])->save();
        } catch (Throwable $e) {
            Log::error('Listening audio generation job failed: '.$e->getMessage(), [
                'question_id' => $this->question->getKey(),
                'exception' => $e,
            ]);

            $freshQuestion = $this->question->fresh();

            if ($freshQuestion instanceof Question) {
                $freshQuestion->forceFill([
                    'generation_mode' => Question::GENERATION_MODE_MANUAL,
                ])->save();
            }

            throw $e;
        }
    }
}
