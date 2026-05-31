<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use App\Jobs\GenerateQuestionAiJob;
use App\Models\Module;
use App\Models\Question;
use App\Services\QuestionFormatResolver;
use App\Services\QuestionGenerationQualityValidator;
use App\Services\QuestionGenerationThemeSelector;
use App\Support\AdminQuestionGenerationMessageTranslator;
use App\Support\QuestionStructuredContent;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    private const AUDIO_VOICE_SESSION_KEY = 'question_resource.audio_voice_preset';

    private const AUDIO_STYLE_SESSION_KEY = 'question_resource.audio_style_preset';

    public function mount(): void
    {
        parent::mount();

        $this->applyPersistedAudioSynthesisPresets();
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->buildGenerateAction(),
        ];
    }

    private function buildGenerateAction(): Action
    {
        return Action::make('generate_with_ai')
            ->label(app(AdminQuestionGenerationMessageTranslator::class)->generateActionLabel())
            ->icon('heroicon-o-sparkles')
            ->color('success')
            ->action(function (): void {
                try {
                    $moduleId = (int) ($this->form->getRawState()['module_id'] ?? 0);
                    $difficulty = (string) ($this->form->getRawState()['difficulty'] ?? 'medium');

                    if ($moduleId === 0) {
                        Notification::make()
                            ->title(app(AdminQuestionGenerationMessageTranslator::class)->moduleMissingTitle())
                            ->body(app(AdminQuestionGenerationMessageTranslator::class)->moduleMissingBody())
                            ->danger()
                            ->send();

                        return;
                    }

                    $module = Module::query()->with('exam')->find($moduleId);

                    if (! $module) {
                        Notification::make()
                            ->title(app(AdminQuestionGenerationMessageTranslator::class)->moduleNotFoundTitle())
                            ->body(app(AdminQuestionGenerationMessageTranslator::class)->moduleNotFoundBody())
                            ->danger()
                            ->send();

                        return;
                    }

                    $theme = app(QuestionGenerationThemeSelector::class)->selectForModule($module);
                    $format = app(QuestionFormatResolver::class)->resolveForModule($module);
                    $nextOrder = Question::where('module_id', $moduleId)->max('order') + 10;
                    $selectedAudioVoicePreset = $this->resolveSelectedAudioVoicePreset();
                    $selectedAudioStylePreset = $this->resolveSelectedAudioStylePreset();

                    $question = Question::create([
                        'module_id' => $moduleId,
                        'format' => $format,
                        'content' => [],
                        'status' => Question::STATUS_DRAFT,
                        'is_active' => false,
                        'generation_mode' => Question::GENERATION_MODE_AI_GENERATING,
                        'difficulty' => $difficulty,
                        'topic' => $theme->title,
                        'source_label' => $theme->source_label,
                        'source_url' => $theme->source_url,
                        'source_notes' => $theme->notes,
                        'audio_voice_preset' => $selectedAudioVoicePreset,
                        'audio_style_preset' => $selectedAudioStylePreset,
                        'order' => $nextOrder,
                    ]);

                    GenerateQuestionAiJob::dispatch($question, [
                        'format' => $format,
                        'difficulty' => $difficulty,
                        'topic_seed' => $theme->prompt_seed,
                        'topic_catalog_title' => $theme->title,
                        'golden_example' => $theme->golden_example ?? '',
                        'module_slug' => $module->slug,
                    ]);

                    Notification::make()
                        ->title('ИИ генерирует вопрос...')
                        ->body('Задача отправлена в фоновую очередь. Текст появится на странице редактирования через несколько минут.')
                        ->success()
                        ->send();

                    $this->redirect(EditQuestion::getUrl(['record' => $question->id]));
                } catch (Throwable $e) {
                    Notification::make()
                        ->title(app(AdminQuestionGenerationMessageTranslator::class)->failedTitle())
                        ->body(app(AdminQuestionGenerationMessageTranslator::class)->translateThrowable($e))
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * @return list<string>
     */
    private function normalizeQualityWarnings(mixed $warnings): array
    {
        if (! is_array($warnings)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function (mixed $warning): ?string {
                if (is_string($warning)) {
                    $trimmed = trim($warning);

                    return $trimmed === '' ? null : $trimmed;
                }

                if (is_scalar($warning) || $warning === null) {
                    $trimmed = trim((string) $warning);

                    return $trimmed === '' ? null : $trimmed;
                }

                return null;
            },
            $warnings,
        )));
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function encodeGeneratedContentForEditor(array $content): string
    {
        return (string) json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function resolveFormatFromModule(int $moduleId): string
    {
        $module = Module::query()->find($moduleId);

        if (! $module) {
            return 'per_gap';
        }

        return app(QuestionFormatResolver::class)->resolveForModule($module);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->validateQuestionContentBeforePersisting($data);
    }

    /**
     * @return list<string>
     */
    private function normalizeReviewGapIds(mixed $gapIds): array
    {
        if (! is_array($gapIds)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function (mixed $gapId): ?string {
                if (! is_string($gapId)) {
                    return null;
                }

                $trimmed = trim($gapId);

                return preg_match('/^gap_\d+$/', $trimmed) === 1 ? $trimmed : null;
            },
            $gapIds,
        )));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateQuestionContentBeforePersisting(array $data): array
    {
        $moduleId = (int) ($data['module_id'] ?? 0);
        $format = $this->resolvePersistedFormat($data, $moduleId);
        $statusFromForm = $data['status'] ?? null;

        if ($statusFromForm === null) {
            $isActive = false;
        } else {
            $isActive = filter_var($statusFromForm, FILTER_VALIDATE_BOOLEAN)
                || in_array($statusFromForm, ['on', '1', 'true', Question::STATUS_PUBLISHED], true);
        }

        $status = $isActive ? Question::STATUS_PUBLISHED : Question::STATUS_DRAFT;

        $data['is_active'] = $isActive;
        $data['status'] = $status;
        $data['format'] = $format;
        $data['generation_mode'] = $data['generation_mode'] ?? Question::GENERATION_MODE_MANUAL;
        $data['content'] = $this->syncStructuredContentIntoPayload(
            $data,
            $format,
        );
        $data['content'] = $this->syncListeningAudioIntoContent(
            is_array($data['content'] ?? null) ? $data['content'] : [],
            $data,
            $format,
        );
        $validator = app(QuestionGenerationQualityValidator::class);
        $report = $validator->validateQuestionContentPayload($data['content'], $format);

        if ($report['errors'] !== []) {
            $translator = app(AdminQuestionGenerationMessageTranslator::class);

            throw ValidationException::withMessages([
                'content' => $translator->translateMessages($report['errors']),
            ]);
        }

        if ($isActive && ($report['explanations_status'] ?? 'failed') !== 'passed') {
            $translator = app(AdminQuestionGenerationMessageTranslator::class);

            Notification::make()
                ->title('Качество объяснений')
                ->body($translator->activationRequiresPassedExplanations())
                ->warning()
                ->send();
        }

        if (! $isActive) {
            unset($data['structured_content']);

            return $data;
        }

        if ($this->requiresAudioSource($format) && $this->resolveAudioUrlFromContent(
            is_array($data['content'] ?? null) ? $data['content'] : [],
        ) === null) {
            throw ValidationException::withMessages([
                'audio_voice_preset' => ['Для публикации listening-задания требуется сгенерированное аудио. Сначала сохраните черновик, затем нажмите «Сгенерировать аудио».'],
                'audio_style_preset' => ['Для публикации listening-задания требуется сгенерированное аудио. Сначала сохраните черновик, затем нажмите «Сгенерировать аудио».'],
            ]);
        }

        if ($this->requiresAudioSource($format) && blank($data['content']['transcript'] ?? null)) {
            throw ValidationException::withMessages([
                'structured_content.transcript' => ['Для публикации listening-задания нужен транскрипт для редакционной проверки и отображения студенту.'],
            ]);
        }

        unset($data['structured_content']);

        return $data;
    }

    private function requiresAudioSource(string $format): bool
    {
        return in_array($format, [
            'listening_segmented_true_false',
            'listening_short_true_false',
            'listening_long_true_false',
        ], true);
    }

    private function minimumRecommendedWordCount(string $format): int
    {
        return match ($format) {
            'shared_pool' => 260,
            'listening_segmented_true_false' => 280,
            'listening_short_true_false' => 45,
            default => 220,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvePersistedFormat(array $data, int $moduleId): string
    {
        $resolvedFormat = $this->resolveFormatFromModule($moduleId);

        if ($this->moduleUsesLockedFormat($moduleId)) {
            return $resolvedFormat;
        }

        return (string) ($data['format'] ?? $resolvedFormat);
    }

    private function moduleUsesLockedFormat(int $moduleId): bool
    {
        $module = Module::query()->find($moduleId);

        return $module?->type === 'listening';
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncListeningAudioIntoContent(array $content, array $data, string $format): array
    {
        if (! $this->requiresAudioSource($format)) {
            return $content;
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function resolveAudioUrlFromContent(array $content): ?string
    {
        $audioUrl = $content['audio']['url'] ?? null;

        return is_string($audioUrl) && $audioUrl !== '' ? $audioUrl : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncStructuredContentIntoPayload(array $data, string $format): array
    {
        $content = is_array($data['content'] ?? null) ? $data['content'] : [];
        $structuredContent = is_array($data['structured_content'] ?? null) ? $data['structured_content'] : [];

        if ($structuredContent === []) {
            return $content;
        }

        return QuestionStructuredContent::mergeIntoContent($content, $structuredContent, $format);
    }

    public function updated(string $name, mixed $value): void
    {
        if (str_ends_with($name, 'audio_style_preset')) {
            $normalizedStylePreset = $this->normalizeAudioStylePreset($value);

            if ($normalizedStylePreset !== null) {
                session()->put(self::AUDIO_STYLE_SESSION_KEY, $normalizedStylePreset);
            }

            return;
        }

        if (! str_ends_with($name, 'audio_voice_preset')) {
            return;
        }

        $normalizedVoicePreset = $this->normalizeAudioVoicePreset($value);

        if ($normalizedVoicePreset === null) {
            return;
        }

        session()->put(self::AUDIO_VOICE_SESSION_KEY, $normalizedVoicePreset);
    }

    private function applyPersistedAudioSynthesisPresets(): void
    {
        $persistedVoicePreset = $this->normalizeAudioVoicePreset(session()->get(self::AUDIO_VOICE_SESSION_KEY));
        $persistedStylePreset = $this->normalizeAudioStylePreset(session()->get(self::AUDIO_STYLE_SESSION_KEY));

        if ($persistedVoicePreset === null && $persistedStylePreset === null) {
            return;
        }

        $this->form->fill([
            ...$this->form->getRawState(),
            'audio_voice_preset' => $persistedVoicePreset ?? Question::AUDIO_VOICE_PRESET_NEWS_FEMALE,
            'audio_style_preset' => $persistedStylePreset ?? Question::AUDIO_STYLE_PRESET_CLEAN,
        ]);
    }

    private function resolveSelectedAudioVoicePreset(): string
    {
        $rawState = $this->form->getRawState();
        $fromForm = $this->normalizeAudioVoicePreset($rawState['audio_voice_preset'] ?? null);
        $fromSession = $this->normalizeAudioVoicePreset(session()->get(self::AUDIO_VOICE_SESSION_KEY));
        $selected = $fromForm ?? $fromSession ?? Question::AUDIO_VOICE_PRESET_NEWS_FEMALE;

        session()->put(self::AUDIO_VOICE_SESSION_KEY, $selected);

        return $selected;
    }

    private function resolveSelectedAudioStylePreset(): string
    {
        $rawState = $this->form->getRawState();
        $fromForm = $this->normalizeAudioStylePreset($rawState['audio_style_preset'] ?? null);
        $fromSession = $this->normalizeAudioStylePreset(session()->get(self::AUDIO_STYLE_SESSION_KEY));
        $selected = $fromForm ?? $fromSession ?? Question::AUDIO_STYLE_PRESET_CLEAN;

        session()->put(self::AUDIO_STYLE_SESSION_KEY, $selected);

        return $selected;
    }

    private function normalizeAudioStylePreset(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        $allowed = array_keys(Question::audioStylePresetOptions());

        if (! in_array($trimmed, $allowed, true)) {
            return null;
        }

        return $trimmed;
    }

    private function normalizeAudioVoicePreset(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        $allowed = array_keys(Question::audioVoicePresetOptions());

        if (! in_array($trimmed, $allowed, true)) {
            return null;
        }

        return $trimmed;
    }
}
