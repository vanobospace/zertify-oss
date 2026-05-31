<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use App\Jobs\GenerateListeningAudioJob;
use App\Jobs\GenerateQuestionAiJob;
use App\Models\Module;
use App\Models\Question;
use App\Services\ListeningQuestionAudioSynthesisService;
use App\Services\QuestionFormatResolver;
use App\Services\QuestionGenerationQualityValidator;
use App\Services\QuestionGenerationThemeSelector;
use App\Support\AdminQuestionGenerationMessageTranslator;
use App\Support\QuestionStructuredContent;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    private const AUDIO_VOICE_SESSION_KEY = 'question_resource.audio_voice_preset';

    private const AUDIO_STYLE_SESSION_KEY = 'question_resource.audio_style_preset';

    public ?string $pendingGenerationType = null;

    public ?int $pendingAudioAssetId = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->applyPersistedAudioSynthesisPresets();

        if ($this->record?->generation_mode === Question::GENERATION_MODE_AI_GENERATING) {
            $this->pendingGenerationType = 'question';
        }

        if ($this->record?->generation_mode === Question::GENERATION_MODE_AI_AUDIO_GENERATING) {
            $this->pendingGenerationType = 'audio';
            $this->pendingAudioAssetId = $this->record?->question_audio_asset_id;
        }
    }

    public function getSubheading(): Htmlable|string|null
    {
        if ($this->record?->generation_mode === Question::GENERATION_MODE_AI_GENERATING) {
            return new HtmlString(Blade::render(<<<'BLADE'
<div wire:poll.3s="checkGenerationStatus" class="ai-loading-box" style="margin-top: 1rem; position: relative; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background-color: rgba(16, 185, 129, 0.2);">
        <div style="height: 100%; width: 50%; background-color: #10b981; animation: filament-bouncing-bar 1.5s cubic-bezier(0.65, 0.815, 0.735, 0.395) infinite normal none running; box-shadow: 0 0 10px #10b981;"></div>
    </div>

    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 1.5rem;">
        <div style="width: 4rem; height: 4rem; display: flex; align-items: center; justify-content: center; border-radius: 9999px; background-color: rgba(16, 185, 129, 0.15); margin-bottom: 1.5rem; box-shadow: 0 0 15px rgba(16, 185, 129, 0.3);">
            <x-filament::icon icon="heroicon-o-sparkles" style="width: 2rem; height: 2rem; color: #10b981; animation: filament-pulse-icon 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;" />
        </div>

        <h4 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; margin-top: 0; color: inherit;">
            ИИ генерирует задание...
        </h4>
        <p style="font-size: 0.875rem; opacity: 0.7; max-width: 32rem; margin: 0 auto; line-height: 1.5;">
            Мы подбираем подходящую лексику B2, пишем реалистичный текст и формируем дистракторы с детальными объяснениями. Пожалуйста, подождите 15-30 секунд. Страница обновится автоматически сразу после завершения.
        </p>
    </div>

    <style>
        .ai-loading-box {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            color: #111827;
        }
        @media (prefers-color-scheme: dark) {
            .ai-loading-box {
                background-color: #18181b;
                border: 1px solid rgba(255, 255, 255, 0.08);
                color: #ffffff;
            }
        }
        @keyframes filament-bouncing-bar {
            0% { transform: translateX(-150%) }
            100% { transform: translateX(300%) }
        }
        @keyframes filament-pulse-icon {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.95); }
        }
    </style>
</div>
BLADE));
        }

        if ($this->record?->generation_mode === Question::GENERATION_MODE_AI_AUDIO_GENERATING) {
            return new HtmlString(Blade::render(<<<'BLADE'
<div wire:poll.3s="checkGenerationStatus" class="ai-loading-box" style="margin-top: 1rem; position: relative; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background-color: rgba(245, 158, 11, 0.2);">
        <div style="height: 100%; width: 50%; background-color: #f59e0b; animation: filament-bouncing-bar 1.5s cubic-bezier(0.65, 0.815, 0.735, 0.395) infinite normal none running; box-shadow: 0 0 10px #f59e0b;"></div>
    </div>

    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 1.5rem;">
        <div style="width: 4rem; height: 4rem; display: flex; align-items: center; justify-content: center; border-radius: 9999px; background-color: rgba(245, 158, 11, 0.15); margin-bottom: 1.5rem; box-shadow: 0 0 15px rgba(245, 158, 11, 0.3);">
            <x-filament::icon icon="heroicon-o-speaker-wave" style="width: 2rem; height: 2rem; color: #f59e0b; animation: filament-pulse-icon 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;" />
        </div>

        <h4 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; margin-top: 0; color: inherit;">
            ИИ генерирует аудио...
        </h4>
        <p style="font-size: 0.875rem; opacity: 0.7; max-width: 32rem; margin: 0 auto; line-height: 1.5;">
            Задача отправлена в очередь. Подождите, пока соберётся WAV-файл и прикрепится к вопросу.
            Страница обновится автоматически.
        </p>
    </div>

    <style>
        .ai-loading-box {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            color: #111827;
        }
        @media (prefers-color-scheme: dark) {
            .ai-loading-box {
                background-color: #18181b;
                border: 1px solid rgba(255, 255, 255, 0.08);
                color: #ffffff;
            }
        }
        @keyframes filament-bouncing-bar {
            0% { transform: translateX(-150%) }
            100% { transform: translateX(300%) }
        }
        @keyframes filament-pulse-icon {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.95); }
        }
    </style>
</div>
BLADE));
        }

        return parent::getSubheading();
    }

    public function checkGenerationStatus(): void
    {
        $this->record?->refresh();

        if (! $this->record) {
            return;
        }

        $stillGenerating = in_array($this->record->generation_mode, [
            Question::GENERATION_MODE_AI_GENERATING,
            Question::GENERATION_MODE_AI_AUDIO_GENERATING,
        ], true);

        if ($stillGenerating) {
            return;
        }

        if ($this->pendingGenerationType === 'audio') {
            if ($this->record->question_audio_asset_id !== null && $this->record->question_audio_asset_id !== $this->pendingAudioAssetId) {
                $this->refreshFormWithLatestAudioState();
                $audioAsset = $this->record->audioAsset;
                $generationMetadata = is_array($audioAsset?->generation_metadata ?? null) ? $audioAsset->generation_metadata : [];
                $fallbackFromProvider = trim((string) ($generationMetadata['fallback_from_provider'] ?? ''));

                if ($fallbackFromProvider !== '') {
                    Notification::make()
                        ->title('Аудио сгенерировано через fallback')
                        ->body('Квота Gemini сейчас недоступна. Для этого файла автоматически использован Google TTS.')
                        ->warning()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Готово!')
                        ->body('Аудио успешно сгенерировано и прикреплено к вопросу.')
                        ->success()
                        ->send();
                }
            } else {
                Notification::make()
                    ->title('Ошибка генерации аудио')
                    ->body('Очередь завершила задачу без нового аудиофайла. Проверьте логи worker и активный speech-провайдер.')
                    ->danger()
                    ->send();
            }

            $this->pendingGenerationType = null;
            $this->pendingAudioAssetId = null;

            return;
        }

        $this->fillForm();

        if ($this->record->generation_mode === Question::GENERATION_MODE_MANUAL) {
            Notification::make()
                ->title('Ошибка генерации')
                ->body('Фоновая задача завершилась с ошибкой (возможно, Gemini API недоступен). Проверьте логи сервера.')
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title('Готово!')
                ->body('ИИ завершил перегенерацию вопроса.')
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->buildGenerateAction(),
            $this->buildGenerateAudioAction(),
            Actions\DeleteAction::make(),
        ];
    }

    private function buildGenerateAction(): Action
    {
        return Action::make('generate_with_ai')
            ->label(app(AdminQuestionGenerationMessageTranslator::class)->regenerateActionLabel())
            ->icon('heroicon-o-sparkles')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(app(AdminQuestionGenerationMessageTranslator::class)->regenerateHeading())
            ->modalDescription(app(AdminQuestionGenerationMessageTranslator::class)->regenerateDescription())
            ->modalSubmitActionLabel(app(AdminQuestionGenerationMessageTranslator::class)->regenerateSubmitLabel())
            ->action(function (): void {
                try {
                    $this->persistAudioVoicePresetFromFormToRecord();
                    $this->persistAudioStylePresetFromFormToRecord();

                    $moduleId = (int) ($this->form->getRawState()['module_id'] ?? $this->record->module_id);
                    $difficulty = (string) ($this->form->getRawState()['difficulty'] ?? $this->record->difficulty ?? 'medium');
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
                    $this->record->update([
                        'status' => Question::STATUS_DRAFT,
                        'is_active' => false,
                        'generation_mode' => Question::GENERATION_MODE_AI_GENERATING,
                    ]);

                    GenerateQuestionAiJob::dispatch($this->record, [
                        'format' => $format,
                        'difficulty' => $difficulty,
                        'topic_seed' => $theme->prompt_seed,
                        'topic_catalog_title' => $theme->title,
                        'golden_example' => $theme->golden_example ?? '',
                        'module_slug' => $module->slug,
                    ]);

                    Notification::make()
                        ->title('ИИ переписывает вопрос...')
                        ->body('Задача отправлена в фоновую очередь. Пожалуйста, подождите.')
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title(app(AdminQuestionGenerationMessageTranslator::class)->failedTitle())
                        ->body(app(AdminQuestionGenerationMessageTranslator::class)->translateThrowable($e))
                        ->danger()
                        ->send();
                }
            });
    }

    private function buildGenerateAudioAction(): Action
    {
        return Action::make('generate_audio')
            ->label(fn (): string => $this->resolveAudioGenerationActionLabel())
            ->icon('heroicon-o-speaker-wave')
            ->color('warning')
            ->visible(fn (): bool => in_array($this->record->resolveFormat(), [
                'listening_segmented_true_false',
                'listening_short_true_false',
                'listening_long_true_false',
            ], true))
            ->requiresConfirmation()
            ->modalHeading(fn (): string => $this->hasAttachedGeneratedAudio()
                ? 'Перегенерировать аудио'
                : 'Сгенерировать аудио')
            ->modalDescription(fn (): string => $this->hasAttachedGeneratedAudio()
                ? 'Текущий WAV-файл будет заменён новым на основе сохранённого транскрипта.'
                : 'Будет создан новый WAV-файл на основе текущего сохранённого транскрипта.')
            ->modalSubmitActionLabel(fn (): string => $this->hasAttachedGeneratedAudio()
                ? 'Перегенерировать'
                : 'Сгенерировать')
            ->action(function (): void {
                try {
                    $this->persistAudioVoicePresetFromFormToRecord();
                    $this->persistAudioStylePresetFromFormToRecord();

                    if ($this->shouldQueueListeningAudioGeneration()) {
                        $previousGenerationMode = (string) ($this->record->generation_mode ?: Question::GENERATION_MODE_MANUAL);
                        $this->pendingGenerationType = 'audio';
                        $this->pendingAudioAssetId = $this->record->question_audio_asset_id;

                        $this->record->update([
                            'generation_mode' => Question::GENERATION_MODE_AI_AUDIO_GENERATING,
                        ]);

                        GenerateListeningAudioJob::dispatch($this->record, $previousGenerationMode);

                        Notification::make()
                            ->title('Генерация аудио поставлена в очередь')
                            ->body('Задача отправлена в очередь. Прогресс отображается выше и обновляется автоматически.')
                            ->success()
                            ->send();

                        return;
                    }

                    $asset = app(ListeningQuestionAudioSynthesisService::class)->synthesizeForQuestion($this->record);

                    $this->refreshFormWithLatestAudioState();

                    Notification::make()
                        ->title('Аудио сгенерировано')
                        ->body("Сгенерированный аудиофайл #{$asset->id} прикреплён к вопросу.")
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Ошибка генерации аудио')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function resolveAudioGenerationActionLabel(): string
    {
        return $this->hasAttachedGeneratedAudio()
            ? 'Перегенерировать аудио'
            : 'Сгенерировать аудио';
    }

    private function hasAttachedGeneratedAudio(): bool
    {
        return filled($this->record?->question_audio_asset_id);
    }

    private function shouldQueueListeningAudioGeneration(): bool
    {
        return $this->record instanceof Question && $this->record->usesListeningAudio();
    }

    private function refreshFormWithLatestAudioState(): void
    {
        if (! $this->record instanceof Question) {
            return;
        }

        $this->record->refresh()->load('audioAsset');

        $this->form->fill([
            ...$this->form->getRawState(),
            'question_audio_asset_id' => $this->record->question_audio_asset_id,
            'audio_source_type' => $this->record->audio_source_type,
            'audio_external_url' => $this->record->audio_external_url,
            'content' => $this->encodeGeneratedContentForEditor($this->record->content ?? []),
            'structured_content' => QuestionStructuredContent::toStructured(
                is_array($this->record->content ?? null) ? $this->record->content : [],
                $this->record->resolveFormat(),
            ),
        ]);
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
        if (! $this->record instanceof Question) {
            return;
        }

        $recordVoicePreset = $this->normalizeAudioVoicePreset($this->record->audio_voice_preset);
        if ($recordVoicePreset !== null) {
            session()->put(self::AUDIO_VOICE_SESSION_KEY, $recordVoicePreset);
        } else {
            $persistedVoicePreset = $this->normalizeAudioVoicePreset(session()->get(self::AUDIO_VOICE_SESSION_KEY));
            if ($persistedVoicePreset !== null) {
                $this->record->forceFill([
                    'audio_voice_preset' => $persistedVoicePreset,
                ])->save();
            }
        }

        $recordPreset = $this->normalizeAudioStylePreset($this->record->audio_style_preset);

        if ($recordPreset !== null) {
            session()->put(self::AUDIO_STYLE_SESSION_KEY, $recordPreset);

        } else {
            $persistedStylePreset = $this->normalizeAudioStylePreset(session()->get(self::AUDIO_STYLE_SESSION_KEY));

            if ($persistedStylePreset !== null) {
                $this->record->forceFill([
                    'audio_style_preset' => $persistedStylePreset,
                ])->save();
            }
        }

        $this->fillForm();
    }

    private function persistAudioStylePresetFromFormToRecord(): void
    {
        if (! $this->record instanceof Question) {
            return;
        }

        $rawState = $this->form->getRawState();
        $selected = $this->normalizeAudioStylePreset($rawState['audio_style_preset'] ?? null);

        if ($selected === null) {
            return;
        }

        session()->put(self::AUDIO_STYLE_SESSION_KEY, $selected);

        if ($this->record->audio_style_preset === $selected) {
            return;
        }

        $this->record->forceFill([
            'audio_style_preset' => $selected,
        ])->save();
    }

    private function persistAudioVoicePresetFromFormToRecord(): void
    {
        if (! $this->record instanceof Question) {
            return;
        }

        $rawState = $this->form->getRawState();
        $selected = $this->normalizeAudioVoicePreset($rawState['audio_voice_preset'] ?? null);

        if ($selected === null) {
            return;
        }

        session()->put(self::AUDIO_VOICE_SESSION_KEY, $selected);

        if ($this->record->audio_voice_preset === $selected) {
            return;
        }

        $this->record->forceFill([
            'audio_voice_preset' => $selected,
        ])->save();
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
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->validateQuestionContentBeforePersisting($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['structured_content'] = QuestionStructuredContent::toStructured(
            is_array($data['content'] ?? null) ? $data['content'] : [],
            is_string($data['format'] ?? null) ? $data['format'] : null,
        );

        return $data;
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
        $moduleId = (int) ($data['module_id'] ?? $this->record->module_id);
        $format = $this->resolvePersistedFormat($data, $moduleId);
        $statusFromForm = $data['status'] ?? null;

        // Handle boolean/string from Toggle or use record's existing status if missing from form
        if ($statusFromForm === null) {
            $isActive = (bool) ($this->record->status === Question::STATUS_PUBLISHED || $this->record->is_active);
        } else {
            $isActive = filter_var($statusFromForm, FILTER_VALIDATE_BOOLEAN)
                || in_array($statusFromForm, ['on', '1', 'true', Question::STATUS_PUBLISHED], true);
        }

        $status = $isActive ? Question::STATUS_PUBLISHED : Question::STATUS_DRAFT;

        $data['is_active'] = $isActive;
        $data['status'] = $status;
        $data['format'] = $format;
        $data['generation_mode'] = $data['generation_mode'] ?? $this->record->generation_mode ?? Question::GENERATION_MODE_MANUAL;
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
                'audio_voice_preset' => ['Для публикации listening-задания требуется сгенерированное аудио. Нажмите «Сгенерировать аудио» перед публикацией.'],
                'audio_style_preset' => ['Для публикации listening-задания требуется сгенерированное аудио. Нажмите «Сгенерировать аудио» перед публикацией.'],
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
            'shared_pool' => 250,
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

        return (string) ($data['format'] ?? $this->record->format ?? $resolvedFormat);
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
        $audioUrl = $content['audio']['url'] ?? $this->record->resolveAudioUrl();

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
}
