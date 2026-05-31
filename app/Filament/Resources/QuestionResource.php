<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Services\QuestionGenerationQualityValidator;
use App\Support\QuestionStructuredContent;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Вопросы';

    protected static ?string $modelLabel = 'вопрос';

    protected static ?string $pluralModelLabel = 'вопросы';

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('module_id')
                    ->label('Модуль')
                    ->options(fn (): array => static::moduleOptions())
                    ->live()
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        $set('format', self::guessFormatFromModule((int) ($state ?? 0)));
                    })
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('difficulty')
                    ->label('Сложность')
                    ->options([
                        'easy' => 'Лёгкая',
                        'medium' => 'Средняя',
                        'hard' => 'Сложная',
                    ])
                    ->default('medium')
                    ->required(),

                Forms\Components\Placeholder::make('generation_flow_notice')
                    ->label('Порядок AI-генерации')
                    ->content(fn (Get $get): HtmlString => self::renderPreformattedText(
                        self::buildGenerationFlowNotice((string) ($get('format') ?? ''), (int) ($get('module_id') ?? 0)),
                    ))
                    ->visible(fn (Get $get): bool => self::formatRequiresAudio(
                        (string) (($get('format') ?? '') ?: (self::guessFormatFromModule((int) ($get('module_id') ?? 0)) ?? '')),
                    ))
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('topic')
                    ->label('Итоговый заголовок')
                    ->placeholder('Для Hören в черновике заполняется ИИ; потом можно отредактировать.')
                    ->helperText(fn (Get $get): ?string => self::formatRequiresAudio(
                        (string) (($get('format') ?? '') ?: (self::guessFormatFromModule((int) ($get('module_id') ?? 0)) ?? '')),
                    )
                        ? 'Для черновиков Hören Gemini сначала создаёт заголовок и транскрипт. Аудио генерируется отдельно из сохранённого транскрипта.'
                        : null)
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Select::make('format')
                    ->label('Формат задания')
                    ->options(fn (Get $get): array => self::resolveAvailableFormats((int) ($get('module_id') ?? 0)))
                    ->required()
                    ->live()
                    ->hidden(fn (Get $get): bool => self::isListeningOnlyResource() || self::moduleUsesLockedFormat((int) ($get('module_id') ?? 0)))
                    ->afterStateHydrated(function (Forms\Components\Select $component, mixed $state, Get $get): void {
                        if (filled($state)) {
                            return;
                        }

                        $component->state(self::guessFormatFromModule((int) ($get('module_id') ?? 0)));
                    }),

                Forms\Components\Select::make('audio_source_type')
                    ->label('Источник аудио')
                    ->options(Question::audioSourceOptions())
                    ->live()
                    ->hidden(fn (Get $get): bool => self::isListeningOnlyResource() || self::formatRequiresAudio((string) ($get('format') ?? ''))),

                Forms\Components\Select::make('question_audio_asset_id')
                    ->label('Загруженный аудиофайл')
                    ->options(fn (): array => QuestionAudioAsset::query()
                        ->where('is_active', true)
                        ->orderByDesc('created_at')
                        ->get()
                        ->mapWithKeys(fn (QuestionAudioAsset $asset): array => [$asset->id => $asset->display_name])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->hidden(fn (Get $get): bool => self::formatRequiresAudio((string) ($get('format') ?? '')))
                    ->visible(fn (Get $get): bool => ! self::formatRequiresAudio((string) ($get('format') ?? '')) && $get('audio_source_type') === Question::AUDIO_SOURCE_ASSET),

                Forms\Components\TextInput::make('audio_external_url')
                    ->label('Внешний URL аудио')
                    ->url()
                    ->maxLength(255)
                    ->hidden(fn (Get $get): bool => self::formatRequiresAudio((string) ($get('format') ?? '')))
                    ->visible(fn (Get $get): bool => ! self::formatRequiresAudio((string) ($get('format') ?? '')) && $get('audio_source_type') === Question::AUDIO_SOURCE_EXTERNAL),

                Forms\Components\Radio::make('audio_voice_preset')
                    ->label('Пресет голоса')
                    ->options(fn (Get $get): array => self::audioVoicePresetOptionsForModule((int) ($get('module_id') ?? 0)))
                    ->default(Question::AUDIO_VOICE_PRESET_NEWS_FEMALE)
                    ->helperText('Этот пресет будет использован при следующей генерации аудио.')
                    ->live()
                    ->inline()
                    ->inlineLabel(false)
                    ->columns(2)
                    ->afterStateHydrated(function (Forms\Components\Radio $component, mixed $state, Get $get): void {
                        $options = self::audioVoicePresetOptionsForModule((int) ($get('module_id') ?? 0));
                        $firstOption = array_key_first($options);

                        if (filled($state) && isset($options[(string) $state])) {
                            return;
                        }

                        $component->state(is_string($firstOption) ? $firstOption : Question::AUDIO_VOICE_PRESET_NEWS_FEMALE);
                    })
                    ->visible(fn (Get $get): bool => self::formatRequiresAudio(
                        (string) (($get('format') ?? '') ?: (self::guessFormatFromModule((int) ($get('module_id') ?? 0)) ?? '')),
                    )),

                Forms\Components\Radio::make('audio_style_preset')
                    ->label('Стиль аудио')
                    ->options(fn (Get $get): array => self::audioStylePresetOptionsForModule((int) ($get('module_id') ?? 0)))
                    ->default(Question::AUDIO_STYLE_PRESET_CLEAN)
                    ->helperText('Этот стиль будет применён к итоговому WAV при следующей генерации аудио.')
                    ->live()
                    ->inline()
                    ->inlineLabel(false)
                    ->columns(3)
                    ->afterStateHydrated(function (Forms\Components\Radio $component, mixed $state, Get $get): void {
                        $options = self::audioStylePresetOptionsForModule((int) ($get('module_id') ?? 0));
                        $firstOption = array_key_first($options);

                        if (filled($state) && isset($options[(string) $state])) {
                            return;
                        }

                        $component->state(is_string($firstOption) ? $firstOption : Question::AUDIO_STYLE_PRESET_CLEAN);
                    })
                    ->visible(fn (Get $get): bool => self::formatRequiresAudio(
                        (string) (($get('format') ?? '') ?: (self::guessFormatFromModule((int) ($get('module_id') ?? 0)) ?? '')),
                    )),

                Forms\Components\Placeholder::make('listening_audio_player')
                    ->label('Предпрослушивание аудио')
                    ->content(fn (Get $get): HtmlString => self::buildAudioPreviewMarkup($get))
                    ->visible(fn (Get $get): bool => self::formatRequiresAudio(
                        (string) (($get('format') ?? '') ?: (self::guessFormatFromModule((int) ($get('module_id') ?? 0)) ?? '')),
                    ))
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('listening_audio_effects')
                    ->label('Применённые эффекты')
                    ->content(fn (Get $get): HtmlString => self::renderPreformattedText(self::buildAppliedAudioEffectsSummary($get)))
                    ->visible(fn (Get $get): bool => self::formatRequiresAudio(
                        (string) (($get('format') ?? '') ?: (self::guessFormatFromModule((int) ($get('module_id') ?? 0)) ?? '')),
                    ))
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('status')
                    ->label('Опубликовано')
                    ->default(false)
                    ->onColor('success')
                    ->offColor('gray')
                    ->helperText(fn (Forms\Components\Toggle $component): string => $component->getState() ? 'Вопрос виден студентам' : 'Черновик — скрыт от студентов')
                    ->live()
                    ->formatStateUsing(fn (mixed $state, ?Question $record): bool => $state === Question::STATUS_PUBLISHED || (bool) ($record?->is_active ?? false))
                    ->dehydrateStateUsing(fn (bool $state): string => $state ? Question::STATUS_PUBLISHED : Question::STATUS_DRAFT),

                Forms\Components\Hidden::make('order')
                    ->default(0),

                Tabs::make('content_tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Информация')
                            ->schema([
                                Forms\Components\Placeholder::make('format_info')
                                    ->label('Формат')
                                    ->content(fn (Get $get): string => self::resolveFormatInfo(
                                        (string) ($get('format') ?? ''),
                                        (int) ($get('module_id') ?? 0),
                                    )['format_label'] ?? 'Сначала выберите модуль и формат')
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('gaps_count_info')
                                    ->label('Количество пропусков')
                                    ->content(fn (Get $get): string => self::buildGapCountSummary(
                                        self::parseEditorContentState($get('content')),
                                        self::resolveFormatInfo(
                                            (string) ($get('format') ?? ''),
                                            (int) ($get('module_id') ?? 0),
                                        ),
                                    ))
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('text_type_info')
                                    ->label('Тип текста')
                                    ->content(fn (Get $get): string => self::resolveFormatInfo(
                                        (string) ($get('format') ?? ''),
                                        (int) ($get('module_id') ?? 0),
                                    )['text_type'] ?? 'Сначала выберите модуль и формат')
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('module_rules')
                                    ->label('Правила модуля')
                                    ->content(fn (Get $get): HtmlString => self::renderPreformattedText(
                                        self::resolveFormatInfo(
                                            (string) ($get('format') ?? ''),
                                            (int) ($get('module_id') ?? 0),
                                        )['rules'] ?? 'Сначала выберите модуль и формат, чтобы увидеть требования к структуре.',
                                    ))
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('json_status')
                                    ->label('Статус JSON')
                                    ->content(fn (Get $get): HtmlString => self::renderPreformattedText(
                                        self::buildJsonStatusSummary(
                                            $get('content'),
                                            self::resolveFormatInfo(
                                                (string) ($get('format') ?? ''),
                                                (int) ($get('module_id') ?? 0),
                                            ),
                                        ),
                                    ))
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('text_preview')
                                    ->label('Текст (первые 300 символов)')
                                    ->content(fn (Get $get): HtmlString => self::renderPreformattedText(
                                        self::buildTextPreview($get('content')),
                                    ))
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('json_template')
                                    ->label('Актуальная JSON-структура')
                                    ->content(fn (Get $get): HtmlString => self::renderPreformattedText(
                                        self::resolveFormatInfo(
                                            (string) ($get('format') ?? ''),
                                            (int) ($get('module_id') ?? 0),
                                        )['json_template'] ?? 'Сначала выберите модуль и формат, чтобы увидеть правильную структуру JSON.',
                                    ))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Structured Editor')
                            ->schema(self::structuredEditorSchema()),

                        Tab::make('JSON Editor')
                            ->schema([
                                Forms\Components\Textarea::make('content')
                                    ->label('JSON Content (vollständige Struktur)')
                                    ->required()
                                    ->rows(28)
                                    ->live(onBlur: true)
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn ($state) => self::formatContentStateForEditor($state))
                                    ->dehydrateStateUsing(fn ($state) => self::decodeContentStateFromEditor($state)),
                            ]),
                    ]),
            ]);
    }

    private static function formatContentStateForEditor(mixed $state): string
    {
        if (is_string($state)) {
            return $state;
        }

        return (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeContentStateFromEditor(mixed $state): array
    {
        return self::parseEditorContentState($state)['content'];
    }

    /**
     * @return array{content: array<string, mixed>, is_empty: bool, json_valid: bool, error: string|null}
     */
    private static function parseEditorContentState(mixed $state): array
    {
        if (is_array($state)) {
            return [
                'content' => $state,
                'is_empty' => $state === [],
                'json_valid' => true,
                'error' => null,
            ];
        }

        if (! is_string($state)) {
            return [
                'content' => [],
                'is_empty' => true,
                'json_valid' => false,
                'error' => null,
            ];
        }

        $trimmed = trim($state);

        if ($trimmed === '') {
            return [
                'content' => [],
                'is_empty' => true,
                'json_valid' => false,
                'error' => null,
            ];
        }

        $decoded = json_decode($state, true);

        if (is_array($decoded)) {
            return [
                'content' => $decoded,
                'is_empty' => false,
                'json_valid' => true,
                'error' => null,
            ];
        }

        if (is_string($decoded)) {
            $decodedAgain = json_decode($decoded, true);

            if (is_array($decodedAgain)) {
                return [
                    'content' => $decodedAgain,
                    'is_empty' => false,
                    'json_valid' => true,
                    'error' => null,
                ];
            }
        }

        return [
            'content' => [],
            'is_empty' => false,
            'json_valid' => false,
            'error' => json_last_error_msg(),
        ];
    }

    private static function resolveModuleSlug(int $moduleId): ?string
    {
        if ($moduleId <= 0) {
            return null;
        }

        return Module::query()->find($moduleId)?->slug;
    }

    /**
     * @return array<string, string>
     */
    private static function audioVoicePresetOptionsForModule(int $moduleId): array
    {
        $slug = self::resolveModuleSlug($moduleId);
        $all = Question::audioVoicePresetOptions();

        if ($slug === 'hoeren-teil-1') {
            return $all;
        }

        if ($slug === 'hoeren-teil-2') {
            return array_filter(
                $all,
                static fn (string $key): bool => in_array($key, [
                    Question::AUDIO_VOICE_PRESET_DIALOG_MF,
                    Question::AUDIO_VOICE_PRESET_DIALOG_FM,
                    Question::AUDIO_VOICE_PRESET_DIALOG_MM,
                    Question::AUDIO_VOICE_PRESET_DIALOG_FF,
                ], true),
                ARRAY_FILTER_USE_KEY,
            );
        }

        if ($slug === 'hoeren-teil-3') {
            return array_filter(
                $all,
                static fn (string $key): bool => in_array($key, [
                    Question::AUDIO_VOICE_PRESET_NEWS_MALE,
                    Question::AUDIO_VOICE_PRESET_NEWS_FEMALE,
                    Question::AUDIO_VOICE_PRESET_NEUTRAL_MALE,
                    Question::AUDIO_VOICE_PRESET_NEUTRAL_FEMALE,
                ], true),
                ARRAY_FILTER_USE_KEY,
            );
        }

        return [
            Question::AUDIO_VOICE_PRESET_NEWS_FEMALE => $all[Question::AUDIO_VOICE_PRESET_NEWS_FEMALE],
            Question::AUDIO_VOICE_PRESET_NEWS_MALE => $all[Question::AUDIO_VOICE_PRESET_NEWS_MALE],
            Question::AUDIO_VOICE_PRESET_NEUTRAL_FEMALE => $all[Question::AUDIO_VOICE_PRESET_NEUTRAL_FEMALE],
            Question::AUDIO_VOICE_PRESET_NEUTRAL_MALE => $all[Question::AUDIO_VOICE_PRESET_NEUTRAL_MALE],
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function audioStylePresetOptionsForModule(int $moduleId): array
    {
        $slug = self::resolveModuleSlug($moduleId);
        $all = Question::audioStylePresetOptions();

        if ($slug === 'hoeren-teil-1') {
            return $all;
        }

        if ($slug === 'hoeren-teil-2') {
            return $all;
        }

        if ($slug === 'hoeren-teil-3') {
            return array_filter(
                $all,
                static fn (string $key): bool => in_array($key, [
                    Question::AUDIO_STYLE_PRESET_CLEAN,
                    Question::AUDIO_STYLE_PRESET_NEWS_POLISH,
                    Question::AUDIO_STYLE_PRESET_RADIO_LIGHT,
                ], true),
                ARRAY_FILTER_USE_KEY,
            );
        }

        return [
            Question::AUDIO_STYLE_PRESET_CLEAN => $all[Question::AUDIO_STYLE_PRESET_CLEAN],
            Question::AUDIO_STYLE_PRESET_NEWS_POLISH => $all[Question::AUDIO_STYLE_PRESET_NEWS_POLISH],
            Question::AUDIO_STYLE_PRESET_RADIO_LIGHT => $all[Question::AUDIO_STYLE_PRESET_RADIO_LIGHT],
        ];
    }

    /**
     * @return list<string>|null
     */
    protected static function moduleTypeFilters(): ?array
    {
        return null;
    }

    private static function isListeningOnlyResource(): bool
    {
        $types = static::moduleTypeFilters();

        if (! is_array($types)) {
            return false;
        }

        return count($types) === 1 && $types[0] === 'listening';
    }

    /**
     * @return array<int, string>
     */
    protected static function moduleOptions(): array
    {
        $query = Module::query()->orderBy('name');
        $types = static::moduleTypeFilters();

        if ($types !== null) {
            $query->whereIn('type', $types);
        }

        return $query->pluck('name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    private static function resolveAvailableFormats(int $moduleId): array
    {
        $module = Module::query()->find($moduleId);

        if (! $module) {
            return [];
        }

        return match ($module->type) {
            'reading' => [
                'reading_matching_headlines' => 'Reading: matching headlines',
                'reading_article_mc' => 'Reading: article + multiple choice',
                'reading_situations_matching' => 'Reading: situations matching',
            ],
            'listening' => [
                'listening_segmented_true_false' => 'Listening: Teil 1 segmented news',
                'listening_short_true_false' => 'Listening: short true/false',
                'listening_long_true_false' => 'Listening: long true/false',
            ],
            default => [
                'per_gap' => 'Sprachbausteine Teil 1',
                'shared_pool' => 'Sprachbausteine Teil 2',
            ],
        };
    }

    private static function guessFormatFromModule(int $moduleId): ?string
    {
        $module = Module::query()->find($moduleId);

        if (! $module) {
            return null;
        }

        return match ($module->type) {
            'reading' => 'reading_matching_headlines',
            'listening' => (string) $module->slug === 'hoeren-teil-1'
                ? 'listening_segmented_true_false'
                : ((string) $module->slug === 'hoeren-teil-2' ? 'listening_long_true_false' : 'listening_short_true_false'),
            default => str_contains((string) $module->slug, 'teil-2') ? 'shared_pool' : 'per_gap',
        };
    }

    private static function moduleUsesLockedFormat(int $moduleId): bool
    {
        $module = Module::query()->find($moduleId);

        return $module?->type === 'listening';
    }

    private static function formatRequiresAudio(string $format): bool
    {
        return in_array($format, [
            'listening_segmented_true_false',
            'listening_short_true_false',
            'listening_long_true_false',
        ], true);
    }

    private static function buildGenerationFlowNotice(string $format, int $moduleId): string
    {
        if ($format === '') {
            $format = self::guessFormatFromModule($moduleId) ?? '';
        }

        if (! self::formatRequiresAudio($format)) {
            return '';
        }

        return implode("\n", [
            '1. Нажмите «Сгенерировать через AI», чтобы получить черновик текста для listening.',
            '2. Gemini заполнит заголовок, транскрипт, аудио-заметки, утверждения, ответы и объяснения.',
            '3. Проверьте транскрипт и сохраните вопрос.',
            '4. Сгенерируйте аудио отдельно из сохранённого транскрипта.',
        ]);
    }

    /**
     * @return array{
     *   format: string,
     *   format_label: string,
     *   text_type: string,
     *   expected_gap_count: int,
     *   expected_pool_count: int|null,
     *   rules: string,
     *   json_template: string
     * }|null
     */
    private static function resolveFormatInfo(string $format, int $moduleId): ?array
    {
        if ($format === '') {
            $format = self::guessFormatFromModule($moduleId) ?? '';
        }

        if ($format === '') {
            return null;
        }

        if ($format === 'shared_pool') {
            return [
                'format' => 'shared_pool',
                'format_label' => 'Shared Pool (Teil 2: общий пул из 15 вариантов)',
                'text_type' => 'Статья / Sachtext',
                'expected_gap_count' => 10,
                'expected_pool_count' => 15,
                'rules' => implode("\n", [
                    'Ожидается 10 пропусков.',
                    'Нужен общий пул из 15 вариантов: 10 правильных + 5 дистракторов.',
                    'В content.format должно быть "shared_pool".',
                    'Текст должен выглядеть как статья минимум из 3 абзацев.',
                    'Минимальная длина текста: 250 слов.',
                ]),
                'json_template' => <<<'TEXT'
{
  "format": "shared_pool",
  "text": "Статья с {{gap_1}} ... {{gap_10}} ...",
  "options_pool": ["dass", "ob", "weil", "jedoch", "daher", "obwohl", "wenn", "denn", "zwar", "dabei", "bereits", "indem", "sodass", "wobei", "als"],
  "correct": {
    "gap_1": "dass",
    "gap_2": "ob",
    "gap_3": "weil",
    "gap_4": "jedoch",
    "gap_5": "daher",
    "gap_6": "obwohl",
    "gap_7": "wenn",
    "gap_8": "denn",
    "gap_9": "indem",
    "gap_10": "als"
  },
  "explanation": {
    "gap_1": {
      "answer": "dass",
      "rule_type": "Konjunktion",
      "reason": "Короткое объяснение, почему именно этот вариант подходит.",
      "pattern": "",
      "contrast": "Почему похожая альтернатива здесь не подходит.",
      "example": "Ich denke, dass das stimmt."
    }
  }
}
TEXT,
            ];
        }

        if ($format === 'reading_matching_headlines') {
            return [
                'format' => 'reading_matching_headlines',
                'format_label' => 'Reading matching (5 текстов + 10 заголовков)',
                'text_type' => '5 коротких текстов',
                'expected_gap_count' => 5,
                'expected_pool_count' => 10,
                'rules' => implode("\n", [
                    'Нужно 5 текстов и 10 заголовков.',
                    'correct должен содержать 5 соответствий text_id -> heading_id.',
                    'Для каждого текста нужно explanation с reason и wrong_answer_reason.',
                ]),
                'json_template' => <<<'TEXT'
{
  "format": "reading_matching_headlines",
  "instructions": "Lesen Sie die Texte und ordnen Sie die Überschriften zu.",
  "headings": [
    { "id": "heading_a", "label": "A", "text": "Eine passende Überschrift" }
  ],
  "texts": [
    { "id": "text_1", "title": "Text 1", "body": "Kurzer Text..." }
  ],
  "correct": {
    "text_1": "heading_a"
  },
  "explanation": {
    "text_1": {
      "correct_answer": "heading_a",
      "reason": "Почему эта Überschrift подходит.",
      "evidence": "Ключевая фраза из текста.",
      "wrong_answer_reason": "Почему другие заголовки не подходят."
    }
  }
}
TEXT,
            ];
        }

        if ($format === 'reading_article_mc') {
            return [
                'format' => 'reading_article_mc',
                'format_label' => 'Reading article + MC (1 текст + 5 вопросов)',
                'text_type' => 'Большая статья',
                'expected_gap_count' => 5,
                'expected_pool_count' => 3,
                'rules' => implode("\n", [
                    'Нужна 1 статья и 5 вопросов.',
                    'У каждого вопроса ровно 3 варианта ответа.',
                    'correct должен содержать 5 соответствий question_id -> option_id.',
                    'Для каждого вопроса нужно explanation.',
                ]),
                'json_template' => <<<'TEXT'
{
  "format": "reading_article_mc",
  "instructions": "Lesen Sie den Text und beantworten Sie die Fragen.",
  "article": {
    "title": "Titel",
    "body": "Langer Artikel..."
  },
  "questions": [
    {
      "id": "question_1",
      "prompt": "Frage 1",
      "options": [
        { "id": "option_a", "label": "a", "text": "Antwort A" },
        { "id": "option_b", "label": "b", "text": "Antwort B" },
        { "id": "option_c", "label": "c", "text": "Antwort C" }
      ]
    }
  ],
  "correct": {
    "question_1": "option_b"
  }
}
TEXT,
            ];
        }

        if ($format === 'reading_situations_matching') {
            return [
                'format' => 'reading_situations_matching',
                'format_label' => 'Reading situations matching (10 ситуаций + 12 текстов)',
                'text_type' => '12 коротких объявлений/описаний',
                'expected_gap_count' => 10,
                'expected_pool_count' => 12,
                'rules' => implode("\n", [
                    'Нужно 10 ситуаций и 12 текстов.',
                    '2 ответа должны оставаться лишними через extra_answer или неиспользованные тексты.',
                    'correct должен содержать 10 соответствий situation_id -> text_id/X.',
                    'Для каждого situation нужен explanation.',
                ]),
                'json_template' => <<<'TEXT'
{
  "format": "reading_situations_matching",
  "instructions": "Lesen Sie die Situationen und ordnen Sie die Texte zu.",
  "situations": [
    { "id": "situation_1", "number": 1, "text": "Beschreibung..." }
  ],
  "texts": [
    { "id": "text_a", "label": "A", "title": "Titel", "body": "Text..." }
  ],
  "extra_answer": { "id": "x", "label": "X", "text": "Kein passender Text" },
  "correct": {
    "situation_1": "text_a"
  }
}
TEXT,
            ];
        }

        if ($format === 'listening_segmented_true_false') {
            return [
                'format' => 'listening_segmented_true_false',
                'format_label' => 'Listening Teil 1 segmented news (anchor + 5 Meldungen)',
                'text_type' => 'Nachrichtensendung mit 5 Meldungen (ca. 2,5-3 Minuten)',
                'expected_gap_count' => 5,
                'expected_pool_count' => 5,
                'rules' => implode("\n", [
                    'Нужен один anchor intro и ровно 5 news segments.',
                    'У каждого сегмента должен быть voice_profile и один statement.',
                    'Из intro + segments должен собираться общий transcript.',
                    'Целевой объём final transcript: примерно 320-420 слов.',
                    'Сегменты должны быть достаточно развёрнутыми, чтобы весь аудиоблок звучал около 3 минут.',
                    'correct должен содержать 5 значений true/false.',
                    'Для публикации обязателен сгенерированный или загруженный audio asset.',
                    'Для каждого сегмента нужна explanation с reason и evidence.',
                ]),
                'json_template' => <<<'TEXT'
{
  "format": "listening_segmented_true_false",
  "instructions": "Sie hören nun eine Nachrichtensendung. Dazu sollen Sie fünf Aufgaben lösen. Sie hören die Nachrichtensendung nur einmal.",
  "audio": {
    "title": "Regionalnachrichten am Morgen",
    "url": "https://example.com/audio.wav",
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
      "segment_text": "Die Stadtbibliothek bleibt in der kommenden Woche wegen einer technischen Modernisierung geschlossen. Bereits ausgeliehene Medien können jedoch rund um die Uhr über die Rückgabebox am Haupteingang abgegeben werden.",
      "statement_id": "statement_1",
      "statement_text": "Die Stadtbibliothek ist in der kommenden Woche nicht geöffnet.",
      "correct_answer": "true",
      "reason": "Die Meldung sagt klar, dass die Bibliothek die ganze nächste Woche geschlossen bleibt.",
      "evidence": "die gesamte nächste Woche geschlossen"
    }
  ]
}
TEXT,
            ];
        }

        if ($format === 'listening_short_true_false') {
            return [
                'format' => 'listening_short_true_false',
                'format_label' => 'Listening short TF (5 утверждений)',
                'text_type' => 'Короткие аудио/сообщения',
                'expected_gap_count' => 5,
                'expected_pool_count' => 2,
                'rules' => implode("\n", [
                    'Нужно аудио и 5 утверждений.',
                    'correct должен содержать 5 значений true/false.',
                    'Для публикации обязателен внешний URL или uploaded audio asset.',
                    'Желательно хранить transcript для редакторки и будущего review/audio QA.',
                    'Для каждого statement нужен explanation.',
                ]),
                'json_template' => <<<'TEXT'
{
  "format": "listening_short_true_false",
  "instructions": "Hören Sie zu und markieren Sie richtig oder falsch.",
  "audio": {
    "title": "Kurzes Audio",
    "url": "https://example.com/audio.mp3",
    "audio_notes": "Kurzer Infoblock, neutral gelesen"
  },
  "transcript": "Guten Morgen. Heute findet der Flohmarkt wegen des Wetters in der Halle statt. ...",
  "statements": [
    { "id": "statement_1", "number": 1, "text": "Aussage 1" }
  ],
  "correct": {
    "statement_1": "true"
  }
}
TEXT,
            ];
        }

        if ($format === 'listening_long_true_false') {
            return [
                'format' => 'listening_long_true_false',
                'format_label' => 'Listening long TF (10 утверждений)',
                'text_type' => 'Большое интервью / история',
                'expected_gap_count' => 10,
                'expected_pool_count' => 2,
                'rules' => implode("\n", [
                    'Нужно аудио и 10 утверждений.',
                    'correct должен содержать 10 значений true/false.',
                    'Для публикации обязателен внешний URL или uploaded audio asset.',
                    'Желательно хранить transcript для редакторки и будущего review/audio QA.',
                    'Для каждого statement нужен explanation с evidence.',
                ]),
                'json_template' => <<<'TEXT'
{
  "format": "listening_long_true_false",
  "instructions": "Hören Sie das Interview und markieren Sie richtig oder falsch.",
  "audio": {
    "title": "Interview",
    "url": "https://example.com/audio.mp3",
    "audio_notes": "Radiointerview mit zwei Stimmen"
  },
  "transcript": "Moderatorin: Willkommen im Studio. Gast: Vielen Dank für die Einladung. ...",
  "context": {
    "speaker": "Moderatorin und Gast",
    "replay_limit": 2
  },
  "statements": [
    { "id": "statement_1", "number": 1, "text": "Aussage 1" }
  ],
  "correct": {
    "statement_1": "false"
  }
}
TEXT,
            ];
        }

        return [
            'format' => 'per_gap',
            'format_label' => 'Per-Gap (Teil 1: 3 варианта на каждый пропуск)',
            'text_type' => 'Письмо / E-Mail',
            'expected_gap_count' => 10,
            'expected_pool_count' => null,
            'rules' => implode("\n", [
                'Ожидается 10 пропусков.',
                'Для каждого gap нужны ровно 3 варианта ответа.',
                'Текст должен выглядеть как письмо минимум из 4 абзацев.',
                'Обычно нужны приветствие и завершающая формула.',
                'Минимальная длина текста: 220 слов.',
            ]),
            'json_template' => <<<'TEXT'
{
  "text": "Письмо с {{gap_1}} ... {{gap_10}} ...",
  "options": {
    "gap_1": ["Option A", "Option B", "Option C"],
    "gap_2": ["Option X", "Option Y", "Option Z"]
  },
  "correct": {
    "gap_1": "Option A",
    "gap_2": "Option Y"
  },
  "explanation": {
    "gap_1": {
      "answer": "Option A",
      "rule_type": "Verb mit Präposition",
      "reason": "Короткое объяснение, почему именно этот вариант подходит.",
      "pattern": "warten auf + Akk.",
      "contrast": "Почему похожая альтернатива здесь не подходит.",
      "example": "Ich warte auf den Bus."
    }
  }
}
TEXT,
        ];
    }

    /**
     * @param  array{content: array<string, mixed>, is_empty: bool, json_valid: bool, error: string|null}  $parsedState
     * @param  array<string, mixed>|null  $moduleInfo
     */
    private static function buildGapCountSummary(array $parsedState, ?array $moduleInfo): string
    {
        if ($moduleInfo === null) {
            return 'Сначала выберите модуль и формат';
        }

        $detectedGapCount = count(is_array($parsedState['content']['correct'] ?? null) ? $parsedState['content']['correct'] : []);

        if ($parsedState['is_empty']) {
            return 'Ожидается ответов: '.$moduleInfo['expected_gap_count'];
        }

        return $moduleInfo['expected_gap_count'].' (в JSON найдено: '.$detectedGapCount.')';
    }

    /**
     * @param  array<string, mixed>|null  $moduleInfo
     */
    private static function buildJsonStatusSummary(mixed $contentState, ?array $moduleInfo): string
    {
        if ($moduleInfo === null) {
            return 'Сначала выберите модуль, чтобы увидеть правила и статус текущего JSON.';
        }

        $parsedState = self::parseEditorContentState($contentState);

        if ($parsedState['is_empty']) {
            return '[i] JSON пока пустой.';
        }

        if (! $parsedState['json_valid']) {
            return '[!] JSON невалиден: '.($parsedState['error'] ?? 'не удалось разобрать структуру');
        }

        $content = $parsedState['content'];
        $correct = is_array($content['correct'] ?? null) ? $content['correct'] : [];
        $gapCount = count($correct);
        $text = (string) ($content['text'] ?? '');
        $wordCount = preg_match_all('/\p{L}+/u', preg_replace('/\{\{gap_\d+\}\}/', '', $text) ?? '');
        $lines = [
            '[OK] JSON валиден.',
            'Пропусков в correct: '.$gapCount.' / '.$moduleInfo['expected_gap_count'],
            'Слов в тексте: '.$wordCount,
        ];

        if ($moduleInfo['format'] === 'shared_pool') {
            $actualFormat = ($content['format'] ?? null) === 'shared_pool' ? 'shared_pool' : 'не указан';
            $pool = is_array($content['options_pool'] ?? null) ? $content['options_pool'] : [];
            $missingAnswers = [];

            foreach ($correct as $answer) {
                if (! in_array($answer, $pool, true)) {
                    $missingAnswers[] = (string) $answer;
                }
            }

            $lines[] = 'content.format: '.$actualFormat;
            $lines[] = 'Пул вариантов: '.count($pool).' / '.($moduleInfo['expected_pool_count'] ?? 15);

            if ($missingAnswers === []) {
                $lines[] = '[OK] Все correct answers есть в options_pool.';
            } else {
                $lines[] = '[!] В options_pool отсутствуют: '.implode(', ', $missingAnswers);
            }
        } elseif ($moduleInfo['format'] === 'per_gap') {
            $options = is_array($content['options'] ?? null) ? $content['options'] : [];
            $validOptionGaps = 0;
            $brokenGaps = [];

            foreach (range(1, 10) as $index) {
                $gapId = "gap_{$index}";
                $gapOptions = $options[$gapId] ?? null;

                if (is_array($gapOptions) && count($gapOptions) === 3) {
                    $validOptionGaps++;

                    continue;
                }

                $brokenGaps[] = $gapId;
            }

            $lines[] = 'Люк с 3 вариантами: '.$validOptionGaps.' / '.$moduleInfo['expected_gap_count'];

            if ($brokenGaps !== []) {
                $lines[] = '[!] Проверьте options для: '.implode(', ', $brokenGaps);
            }
        } elseif ($moduleInfo['format'] === 'reading_matching_headlines') {
            $headings = is_array($content['headings'] ?? null) ? $content['headings'] : [];
            $texts = is_array($content['texts'] ?? null) ? $content['texts'] : [];
            $lines[] = 'Заголовков: '.count($headings).' / '.($moduleInfo['expected_pool_count'] ?? 10);
            $lines[] = 'Текстов: '.count($texts).' / '.$moduleInfo['expected_gap_count'];
        } elseif ($moduleInfo['format'] === 'reading_article_mc') {
            $questions = is_array($content['questions'] ?? null) ? $content['questions'] : [];
            $lines[] = 'Вопросов: '.count($questions).' / '.$moduleInfo['expected_gap_count'];
        } elseif ($moduleInfo['format'] === 'reading_situations_matching') {
            $situations = is_array($content['situations'] ?? null) ? $content['situations'] : [];
            $texts = is_array($content['texts'] ?? null) ? $content['texts'] : [];
            $lines[] = 'Ситуаций: '.count($situations).' / '.$moduleInfo['expected_gap_count'];
            $lines[] = 'Текстов: '.count($texts).' / '.($moduleInfo['expected_pool_count'] ?? 12);
            $lines[] = array_key_exists('extra_answer', $content) ? '[OK] extra_answer задан.' : '[!] Не хватает extra_answer.';
        } elseif (self::formatRequiresAudio($moduleInfo['format'])) {
            $statements = is_array($content['statements'] ?? null) ? $content['statements'] : [];
            $hasAudioTitle = filled($content['audio']['title'] ?? null);
            $hasAudioUrl = filled($content['audio']['url'] ?? null);
            $lines[] = 'Утверждений: '.count($statements).' / '.$moduleInfo['expected_gap_count'];
            $lines[] = $hasAudioTitle ? '[OK] Указан audio.title.' : '[!] Не хватает audio.title.';
            $lines[] = $hasAudioUrl ? '[OK] Указан audio.url.' : '[i] audio.url может быть подставлен из metadata-полей вопроса.';
        }

        return implode("\n", $lines);
    }

    private static function buildTextPreview(mixed $contentState): string
    {
        $parsedState = self::parseEditorContentState($contentState);

        if ($parsedState['is_empty']) {
            return 'Пока нет текста.';
        }

        if (! $parsedState['json_valid']) {
            return 'Предпросмотр недоступен, пока JSON невалиден.';
        }

        $text = trim((string) ($parsedState['content']['text'] ?? ($parsedState['content']['transcript'] ?? '')));

        if ($text === '') {
            return 'В JSON пока нет поля text/transcript.';
        }

        return mb_strimwidth($text, 0, 300, '…');
    }

    private static function renderPreformattedText(string $text): HtmlString
    {
        return new HtmlString('<pre class="whitespace-pre-wrap text-xs leading-5 text-gray-300">'.e($text).'</pre>');
    }

    /**
     * @return array<int, Component>
     */
    private static function structuredEditorSchema(): array
    {
        return [
            self::listeningSegmentedStructuredSection(),
            self::listeningStructuredSection('listening_short_true_false'),
            self::listeningStructuredSection('listening_long_true_false'),
            self::readingMatchingStructuredSection(),
            self::readingArticleStructuredSection(),
            self::readingSituationsStructuredSection(),
        ];
    }

    private static function listeningSegmentedStructuredSection(): Section
    {
        return Section::make('Listening Teil 1 Segmented Editor')
            ->visible(fn (Get $get): bool => (string) ($get('format') ?? '') === 'listening_segmented_true_false')
            ->schema([
                Forms\Components\Placeholder::make('listening_segmented_true_false_readiness')
                    ->label('Readiness')
                    ->content(fn (Get $get): HtmlString => self::renderPreformattedText(
                        self::buildListeningReadinessSummary($get),
                    ))
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('listening_segmented_true_false_audio_preview')
                    ->label('Resolved audio preview')
                    ->content(fn (Get $get): HtmlString => self::buildAudioPreviewMarkup($get))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('structured_content.instructions')
                    ->label('Instructions')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('structured_content.audio_title')
                    ->label('Audio title')
                    ->required(),
                Forms\Components\Textarea::make('structured_content.audio_notes')
                    ->label('Audio notes')
                    ->rows(3)
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('structured_content.intro_voice_profile')
                            ->label('Anchor voice profile')
                            ->required(),
                        Forms\Components\Textarea::make('structured_content.intro_text')
                            ->label('Anchor intro')
                            ->rows(3)
                            ->required(),
                    ]),
                Forms\Components\Textarea::make('structured_content.transcript')
                    ->label('Derived final transcript')
                    ->rows(8)
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('structured_content.segments')
                    ->label('News segments')
                    ->columnSpanFull()
                    ->collapsible()
                    ->cloneable(false)
                    ->reorderable(false)
                    ->defaultItems(5)
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Segment ID')
                            ->required(),
                        Forms\Components\TextInput::make('number')
                            ->label('Number')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('voice_profile')
                            ->label('Voice profile')
                            ->required(),
                        Forms\Components\TextInput::make('statement_id')
                            ->label('Statement ID')
                            ->required(),
                        Forms\Components\Radio::make('correct_answer')
                            ->label('Correct answer')
                            ->options([
                                'true' => 'Richtig',
                                'false' => 'Falsch',
                            ])
                            ->inline()
                            ->required(),
                        Forms\Components\Textarea::make('segment_text')
                            ->label('Segment text')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('statement_text')
                            ->label('Statement text')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Why correct')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('evidence')
                            ->label('Evidence / transcript anchor')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('wrong_answer_reason')
                            ->label('Why wrong answer is wrong')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('strategy_hint')
                            ->label('Strategy hint')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function listeningStructuredSection(string $format): Section
    {
        $isLongFormat = $format === 'listening_long_true_false';

        return Section::make($isLongFormat ? 'Listening Long Editor' : 'Listening Short Editor')
            ->visible(fn (Get $get): bool => (string) ($get('format') ?? '') === $format)
            ->schema([
                Forms\Components\Placeholder::make("{$format}_readiness")
                    ->label('Readiness')
                    ->content(fn (Get $get): HtmlString => self::renderPreformattedText(
                        self::buildListeningReadinessSummary($get),
                    ))
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make("{$format}_audio_preview")
                    ->label('Resolved audio preview')
                    ->content(fn (Get $get): HtmlString => self::buildAudioPreviewMarkup($get))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('structured_content.instructions')
                    ->label('Instructions')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('structured_content.audio_title')
                    ->label('Audio title')
                    ->required(),
                Forms\Components\Textarea::make('structured_content.audio_notes')
                    ->label('Audio notes')
                    ->rows(3),
                Forms\Components\Textarea::make('structured_content.transcript')
                    ->label('Transcript')
                    ->rows(8)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('structured_content.speaker')
                    ->label('Speaker / context')
                    ->visible($isLongFormat),
                Forms\Components\TextInput::make('structured_content.replay_limit')
                    ->label('Replay limit')
                    ->numeric()
                    ->minValue(1)
                    ->visible($isLongFormat),
                Forms\Components\Repeater::make('structured_content.statements')
                    ->label('Statements')
                    ->columnSpanFull()
                    ->collapsible()
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->defaultItems($isLongFormat ? 10 : 5)
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Statement ID')
                            ->required(),
                        Forms\Components\TextInput::make('number')
                            ->label('Number')
                            ->numeric()
                            ->required(),
                        Forms\Components\Radio::make('correct_answer')
                            ->label('Correct answer')
                            ->options([
                                'true' => 'Richtig',
                                'false' => 'Falsch',
                            ])
                            ->inline()
                            ->required(),
                        Forms\Components\Textarea::make('text')
                            ->label('Statement text')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Why correct')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('evidence')
                            ->label('Evidence / transcript anchor')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('wrong_answer_reason')
                            ->label('Why wrong answer is wrong')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('strategy_hint')
                            ->label('Strategy hint')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function readingMatchingStructuredSection(): Section
    {
        return Section::make('Reading Teil 1 Editor')
            ->visible(fn (Get $get): bool => (string) ($get('format') ?? '') === 'reading_matching_headlines')
            ->schema([
                Forms\Components\Textarea::make('structured_content.instructions')
                    ->label('Instructions')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('structured_content.headings')
                    ->label('Headings')
                    ->columnSpanFull()
                    ->collapsible()
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->defaultItems(10)
                    ->schema([
                        Forms\Components\TextInput::make('id')->label('Heading ID')->required(),
                        Forms\Components\TextInput::make('label')->label('Label')->required(),
                        Forms\Components\Textarea::make('text')
                            ->label('Heading text')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Repeater::make('structured_content.texts')
                    ->label('Texts')
                    ->columnSpanFull()
                    ->collapsible()
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->defaultItems(5)
                    ->schema([
                        Forms\Components\TextInput::make('id')->label('Text ID')->required(),
                        Forms\Components\TextInput::make('title')->label('Title'),
                        Forms\Components\TextInput::make('correct_answer')
                            ->label('Correct heading ID')
                            ->helperText('Use a heading ID, e.g. heading_a.')
                            ->required(),
                        Forms\Components\Textarea::make('body')
                            ->label('Body')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('reason')->label('Why correct')->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('evidence')->label('Evidence')->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('wrong_answer_reason')->label('Why other headlines do not fit')->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('strategy_hint')->label('Strategy hint')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }

    private static function readingArticleStructuredSection(): Section
    {
        return Section::make('Reading Teil 2 Editor')
            ->visible(fn (Get $get): bool => (string) ($get('format') ?? '') === 'reading_article_mc')
            ->schema([
                Forms\Components\Textarea::make('structured_content.instructions')
                    ->label('Instructions')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('structured_content.article_title')
                    ->label('Article title')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('structured_content.article_body')
                    ->label('Article body')
                    ->rows(10)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('structured_content.questions')
                    ->label('Questions')
                    ->columnSpanFull()
                    ->collapsible()
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->defaultItems(5)
                    ->schema([
                        Forms\Components\TextInput::make('id')->label('Question ID')->required(),
                        Forms\Components\Textarea::make('prompt')
                            ->label('Prompt')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('correct_answer')
                            ->label('Correct option ID')
                            ->helperText('Use an option ID, e.g. q1_b.')
                            ->required(),
                        Forms\Components\Repeater::make('options')
                            ->label('Options')
                            ->defaultItems(3)
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\TextInput::make('id')->label('Option ID')->required(),
                                Forms\Components\TextInput::make('label')->label('Label')->required(),
                                Forms\Components\Textarea::make('text')
                                    ->label('Option text')
                                    ->rows(2)
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Textarea::make('reason')->label('Why correct')->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('evidence')->label('Evidence')->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('wrong_answer_reason')->label('Why other options do not fit')->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('strategy_hint')->label('Strategy hint')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }

    private static function readingSituationsStructuredSection(): Section
    {
        return Section::make('Reading Teil 3 Editor')
            ->visible(fn (Get $get): bool => (string) ($get('format') ?? '') === 'reading_situations_matching')
            ->schema([
                Forms\Components\Textarea::make('structured_content.instructions')
                    ->label('Instructions')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('structured_content.situations')
                    ->label('Situations')
                    ->columnSpanFull()
                    ->collapsible()
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->defaultItems(10)
                    ->schema([
                        Forms\Components\TextInput::make('id')->label('Situation ID')->required(),
                        Forms\Components\TextInput::make('number')->label('Number')->numeric()->required(),
                        Forms\Components\TextInput::make('correct_answer')
                            ->label('Correct text ID / X')
                            ->helperText('Use a text ID or x.')
                            ->required(),
                        Forms\Components\Textarea::make('text')
                            ->label('Situation text')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('reason')->label('Why correct')->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('evidence')->label('Evidence')->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('wrong_answer_reason')->label('Why others do not fit')->rows(2)->columnSpanFull(),
                        Forms\Components\Textarea::make('strategy_hint')->label('Strategy hint')->rows(2)->columnSpanFull(),
                    ]),
                Forms\Components\Repeater::make('structured_content.texts')
                    ->label('Texts')
                    ->columnSpanFull()
                    ->collapsible()
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->defaultItems(12)
                    ->schema([
                        Forms\Components\TextInput::make('id')->label('Text ID')->required(),
                        Forms\Components\TextInput::make('label')->label('Label')->required(),
                        Forms\Components\TextInput::make('title')->label('Title'),
                        Forms\Components\Textarea::make('body')
                            ->label('Body')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('structured_content.extra_answer_id')
                            ->label('Extra answer ID')
                            ->default('x')
                            ->required(),
                        Forms\Components\TextInput::make('structured_content.extra_answer_label')
                            ->label('Extra answer label')
                            ->default('X')
                            ->required(),
                        Forms\Components\TextInput::make('structured_content.extra_answer_text')
                            ->label('Extra answer text')
                            ->default('Kein passender Text')
                            ->required(),
                    ]),
            ]);
    }

    private static function buildListeningReadinessSummary(Get $get): string
    {
        $format = (string) ($get('format') ?? '');

        if (! self::formatRequiresAudio($format)) {
            return 'Проверка готовности в структурированном виде показывается только для listening-форматов.';
        }

        $content = self::mergedContentForEditorState($get, $format);
        $report = app(QuestionGenerationQualityValidator::class)
            ->validateQuestionContentPayload($content, $format);
        $audioUrl = self::resolveAudioPreviewUrl($get);
        $isPublished = ($get('status') ?? Question::STATUS_DRAFT) === Question::STATUS_PUBLISHED;
        $missing = [];

        if (blank($content['audio']['title'] ?? null)) {
            $missing[] = 'заголовок аудио';
        }

        if ($audioUrl === null) {
            $missing[] = 'источник аудио';
        }

        if (blank($content['transcript'] ?? null)) {
            $missing[] = 'транскрипт';
        }

        $lines = [
            $audioUrl !== null ? '[OK] Источник аудио определён.' : '[!] Источник аудио отсутствует.',
            blank($content['transcript'] ?? null) ? '[!] Транскрипт отсутствует.' : '[OK] Транскрипт заполнен.',
        ];

        if ($report['errors'] === []) {
            $lines[] = '[OK] Структура контента проходит валидацию.';
        } else {
            $lines[] = '[!] Найдены проблемы валидации:';
            foreach ($report['errors'] as $error) {
                $lines[] = ' - '.$error;
            }
        }

        if ($isPublished) {
            $lines[] = $missing === []
                ? '[OK] Готово к публикации для listening.'
                : '[!] Публикация заблокирована: '.implode(', ', $missing).'.';
        }

        return implode("\n", $lines);
    }

    private static function buildAudioPreviewMarkup(Get $get): HtmlString
    {
        if (self::hasStaleAudioPreviewAsset($get)) {
            return self::renderPreformattedText(
                "Прикреплённое аудио устарело.\nТекущий транскрипт больше не совпадает с сгенерированным файлом. Перегенерируйте аудио для обновления предпрослушивания."
            );
        }

        $audioUrl = self::resolveAudioPreviewUrl($get);

        if ($audioUrl === null) {
            return self::renderPreformattedText('URL аудио пока не определён. Сначала сгенерируйте аудио.');
        }

        return new HtmlString(
            '<div class="space-y-3">'.
            '<audio class="w-full" controls preload="metadata" src="'.e($audioUrl).'"></audio>'.
            '<div class="text-xs leading-5 text-gray-300 break-all">'.e($audioUrl).'</div>'.
            '</div>'
        );
    }

    private static function buildAppliedAudioEffectsSummary(Get $get): string
    {
        $assetId = $get('question_audio_asset_id');

        if (! is_numeric($assetId)) {
            return 'Аудиофайл ещё не прикреплён. Сначала выполните «Сгенерировать аудио».';
        }

        $asset = QuestionAudioAsset::query()->find((int) $assetId);

        if (! $asset instanceof QuestionAudioAsset) {
            return 'Метаданные аудио недоступны.';
        }

        $metadata = is_array($asset->generation_metadata ?? null) ? $asset->generation_metadata : [];
        $effects = is_array($metadata['effects_profile'] ?? null) ? $metadata['effects_profile'] : [];

        $lines = [
            'Провайдер: '.((string) ($metadata['provider'] ?? '—')),
            'Модель: '.((string) ($metadata['model'] ?? '—')),
            'Пресет голоса: '.((string) ($metadata['voice_preset'] ?? '—')),
            'Стиль аудио: '.((string) ($metadata['style_preset'] ?? '—')),
            'Профиль эффектов: '.((string) ($effects['profile'] ?? '—')),
            'Эффекты включены: '.(((bool) ($effects['enabled'] ?? false)) ? 'да' : 'нет'),
            'Эффекты применены: '.(((bool) ($effects['applied'] ?? false)) ? 'да' : 'нет'),
            'Стартовый сигнал: '.(((bool) ($effects['intro_signal_enabled'] ?? false)) ? 'да' : 'нет'),
            'Финальный гонг: '.(((bool) ($effects['final_gong_enabled'] ?? false)) ? 'да' : 'нет'),
            'Громкость эффектов (dB): '.((string) ($effects['effects_gain_db'] ?? '—')),
            'Целевой уровень речи (LUFS): '.((string) ($effects['speech_target_lufs'] ?? '—')),
        ];

        $operations = $effects['operations'] ?? null;

        if (is_array($operations) && $operations !== []) {
            $lines[] = 'Операции: '.implode(', ', array_map(static fn (mixed $item): string => (string) $item, $operations));
        }

        return implode("\n", $lines);
    }

    private static function resolveAudioPreviewUrl(Get $get): ?string
    {
        $audioSourceType = $get('audio_source_type');

        if ($audioSourceType === Question::AUDIO_SOURCE_EXTERNAL && filled($get('audio_external_url'))) {
            return (string) $get('audio_external_url');
        }

        if ($audioSourceType === Question::AUDIO_SOURCE_ASSET && filled($get('question_audio_asset_id'))) {
            if (self::hasStaleAudioPreviewAsset($get)) {
                return null;
            }

            return QuestionAudioAsset::query()->find($get('question_audio_asset_id'))?->public_url;
        }

        $content = self::mergedContentForEditorState($get, (string) ($get('format') ?? ''));
        $embeddedAudioUrl = $content['audio']['url'] ?? null;

        return is_string($embeddedAudioUrl) && $embeddedAudioUrl !== '' ? $embeddedAudioUrl : null;
    }

    private static function hasStaleAudioPreviewAsset(Get $get): bool
    {
        if ($get('audio_source_type') !== Question::AUDIO_SOURCE_ASSET || ! filled($get('question_audio_asset_id'))) {
            return false;
        }

        $asset = QuestionAudioAsset::query()->find($get('question_audio_asset_id'));

        if ($asset === null || blank($asset->transcript_hash)) {
            return false;
        }

        $format = (string) ($get('format') ?? '');
        $content = self::mergedContentForEditorState($get, $format);
        $transcript = trim((string) ($content['transcript'] ?? ''));

        if ($transcript === '') {
            return false;
        }

        return ! hash_equals((string) $asset->transcript_hash, hash('sha256', $transcript));
    }

    /**
     * @return array<string, mixed>
     */
    private static function mergedContentForEditorState(Get $get, string $format): array
    {
        $parsedContent = self::parseEditorContentState($get('content'));
        $content = $parsedContent['content'];
        $structured = $get('structured_content');

        if (! is_array($structured) || $structured === []) {
            return $content;
        }

        return QuestionStructuredContent::mergeIntoContent($content, $structured, $format);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                Tables\Columns\TextColumn::make('topic')
                    ->label('Topic')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('difficulty')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('format')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === Question::STATUS_PUBLISHED ? 'success' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->relationship('module', 'name')
                    ->label('Modul'),

                Tables\Filters\SelectFilter::make('difficulty')
                    ->options([
                        'easy' => 'Easy',
                        'medium' => 'Medium',
                        'hard' => 'Hard',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options(Question::statusOptions()),
            ])
            ->groups([
                Group::make('module.name')
                    ->label('Modul')
                    ->collapsible(),
            ])
            ->defaultGroup('module.name')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'asc')
            ->modifyQueryUsing(fn ($query) => $query->with('module.exam'));
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $types = static::moduleTypeFilters();

        if ($types !== null) {
            $query->whereHas('module', fn (Builder $builder) => $builder->whereIn('type', $types));
        }

        return $query;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
