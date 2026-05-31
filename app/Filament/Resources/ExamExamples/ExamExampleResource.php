<?php

namespace App\Filament\Resources\ExamExamples;

use App\Filament\Resources\ExamExamples\Pages\ManageExamExamples;
use App\Filament\Resources\ExamExamples\Pages\ViewExamExample;
use App\Models\ExamExample;
use App\Models\ExamExampleSource;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ExamExampleResource extends Resource
{
    protected static ?string $model = ExamExample::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationLabel = 'Образцы';

    protected static ?string $modelLabel = 'образец';

    protected static ?string $pluralModelLabel = 'банк образцов';

    protected static string|\UnitEnum|null $navigationGroup = 'Банк образцов';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Readable preview')
                    ->schema([
                        Forms\Components\Placeholder::make('full_exercise_preview')
                            ->label('Full exercise')
                            ->content(fn (?ExamExample $record): HtmlString => new HtmlString(
                                '<div class="w-full max-w-full min-w-0 overflow-hidden rounded-lg border border-gray-800 bg-gray-950/60 p-4"><div class="max-h-[70vh] w-full max-w-full min-w-0 overflow-x-auto overflow-y-auto"><pre class="m-0 w-full max-w-full min-w-0 whitespace-pre-wrap break-words text-sm leading-6" style="display:block;overflow-wrap:anywhere;word-break:break-word;white-space:pre-wrap;">'.e(static::formatFullExercisePreview($record)).'</pre></div></div>',
                            ))
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('source_page_previews')
                            ->label('Source pages')
                            ->content(fn (?ExamExample $record): HtmlString => new HtmlString(
                                static::renderSourcePagePreviews($record),
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Overview')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('example_key')
                            ->label('Example key')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('exam_family')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('exam_code')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('variant')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('level')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('module_slug')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('part_key')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('task_shape')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('source_title')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('source_path')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Technical details')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('search_text')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('raw_text')
                            ->label('Raw text')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(14)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('normalized_payload')
                            ->label('Normalized payload')
                            ->formatStateUsing(static fn (mixed $state): string => is_array($state)
                                ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : '')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(18)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('editorial_notes')
                            ->label('Editorial notes')
                            ->formatStateUsing(static fn (mixed $state): string => is_array($state)
                                ? implode("\n", $state)
                                : '')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('rights_note')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('corpus_hash')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(['title', 'search_text', 'raw_text'])
                    ->wrap(),
                Tables\Columns\TextColumn::make('exam_code')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('module_slug')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('part_key')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task_shape')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('source_title')
                    ->label('Source')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TagsColumn::make('tags')
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('raw_text')
                    ->label('Preview')
                    ->formatStateUsing(fn (?string $state): string => Str::limit((string) $state, 120))
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_family')
                    ->options([
                        'telc' => 'telc',
                        'goethe' => 'Goethe',
                        'oesd' => 'ÖSD',
                        'dtz' => 'DTZ',
                    ]),
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'A1' => 'A1',
                        'A2' => 'A2',
                        'B1' => 'B1',
                        'B2' => 'B2',
                        'C1' => 'C1',
                    ]),
                Tables\Filters\SelectFilter::make('module_slug')
                    ->options([
                        'lesen-teil-1' => 'Lesen Teil 1',
                        'lesen-teil-2' => 'Lesen Teil 2',
                        'lesen-teil-3' => 'Lesen Teil 3',
                        'sprachbausteine-teil-1' => 'Sprachbausteine Teil 1',
                        'sprachbausteine-teil-2' => 'Sprachbausteine Teil 2',
                        'hoeren-teil-1' => 'Hören Teil 1',
                        'hoeren-teil-2' => 'Hören Teil 2',
                        'hoeren-teil-3' => 'Hören Teil 3',
                    ]),
                Tables\Filters\SelectFilter::make('task_shape')
                    ->options([
                        'reading_matching_headlines' => 'reading_matching_headlines',
                        'reading_article_mc' => 'reading_article_mc',
                        'reading_situations_matching' => 'reading_situations_matching',
                        'sprachbausteine_per_gap' => 'sprachbausteine_per_gap',
                        'sprachbausteine_shared_pool' => 'sprachbausteine_shared_pool',
                        'listening_segmented_true_false' => 'listening_segmented_true_false',
                        'listening_long_true_false' => 'listening_long_true_false',
                        'listening_short_true_false' => 'listening_short_true_false',
                    ]),
                Tables\Filters\SelectFilter::make('source_id')
                    ->relationship('source', 'title')
                    ->label('Source'),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Смотреть')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ExamExample $record): string => static::getUrl('view', ['record' => $record])),
            ])
            ->groups([
                Group::make('module_slug')
                    ->label('Teil / Modul')
                    ->collapsible(),
                Group::make('part_key')
                    ->label('Teil')
                    ->collapsible(),
            ])
            ->defaultGroup('module_slug')
            ->bulkActions([])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExamExamples::route('/'),
            'view' => ViewExamExample::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        $visibleSecondaryExampleKeys = ExamExampleSource::query()
            ->where('metadata->corpus_role', 'secondary_visible')
            ->get()
            ->flatMap(function (ExamExampleSource $source): array {
                return array_values((array) data_get($source->metadata, 'visible_example_keys', []));
            })
            ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
            ->unique()
            ->values()
            ->all();

        return parent::getEloquentQuery()
            ->with('source')
            ->where(function (Builder $query) use ($visibleSecondaryExampleKeys): void {
                $query
                    ->where('is_canonical_structure_source', true)
                    ->orWhereHas('source', function (Builder $sourceQuery): void {
                        $sourceQuery
                            ->where('is_canonical_structure_source', true)
                            ->orWhere('metadata->corpus_role', 'primary');
                    });

                if ($visibleSecondaryExampleKeys !== []) {
                    $query->orWhereIn('example_key', $visibleSecondaryExampleKeys);
                }
            });
    }

    public static function formatStructuredSection(?ExamExample $record, array $keys): string
    {
        if (! $record instanceof ExamExample || ! is_array($record->normalized_payload)) {
            return 'No structured preview available.';
        }

        $blocks = collect($keys)
            ->map(function (string $key) use ($record): ?string {
                $value = data_get($record->normalized_payload, $key);

                if (! filled($value)) {
                    return null;
                }

                return match (true) {
                    is_array($value) => strtoupper(str_replace('_', ' ', $key)).":\n".static::formatStructuredValue($value),
                    default => strtoupper(str_replace('_', ' ', $key)).":\n".(string) $value,
                };
            })
            ->filter()
            ->values()
            ->all();

        if ($blocks === []) {
            return 'No structured preview available.';
        }

        return implode("\n\n", $blocks);
    }

    public static function formatFullExercisePreview(?ExamExample $record): string
    {
        if (! $record instanceof ExamExample || ! is_array($record->normalized_payload)) {
            return 'No structured preview available.';
        }

        return match ($record->task_shape) {
            'reading_situations_matching' => static::formatReadingSituationsExercise($record->normalized_payload),
            'reading_matching_headlines' => static::formatReadingMatchingHeadlinesExercise($record->normalized_payload),
            'reading_article_mc' => static::formatReadingArticleMcExercise($record->normalized_payload),
            'sprachbausteine_per_gap' => static::formatSprachbausteinePerGapExercise($record->normalized_payload),
            'sprachbausteine_shared_pool' => static::formatSprachbausteineSharedPoolExercise($record->normalized_payload),
            'listening_segmented_true_false', 'listening_long_true_false', 'listening_short_true_false' => static::formatListeningExercise($record->normalized_payload),
            default => static::formatGenericExercise($record->normalized_payload),
        };
    }

    protected static function hasStandaloneFullExercisePreview(?ExamExample $record): bool
    {
        return $record instanceof ExamExample && is_array($record->normalized_payload);
    }

    /**
     * @param  array<int|string, mixed>  $value
     */
    public static function formatStructuredValue(array $value): string
    {
        return collect($value)
            ->map(function (mixed $item, int|string $key): string {
                if (is_array($item)) {
                    if (array_is_list($item)) {
                        return static::formatListItem($key, implode(' | ', array_map(static fn (mixed $entry): string => (string) $entry, $item)));
                    }

                    $line = collect($item)
                        ->map(fn (mixed $entry, int|string $entryKey): string => "{$entryKey}: ".(is_scalar($entry) ? (string) $entry : json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)))
                        ->implode(' | ');

                    return static::formatListItem($key, $line);
                }

                return static::formatListItem($key, (string) $item);
            })
            ->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected static function formatReadingSituationsExercise(array $payload): string
    {
        $blocks = [];

        if (filled($payload['instructions'] ?? null)) {
            $blocks[] = "INSTRUCTIONS:\n".$payload['instructions'];
        }

        if (is_array($payload['situations'] ?? null) && $payload['situations'] !== []) {
            $situations = collect($payload['situations'])
                ->values()
                ->map(fn (mixed $situation, int $index): string => (11 + $index).' '.(string) $situation)
                ->implode("\n");

            $blocks[] = "SITUATIONS:\n".$situations;
        }

        if (is_array($payload['info_texts'] ?? null) && $payload['info_texts'] !== []) {
            $infoTexts = collect($payload['info_texts'])
                ->map(fn (mixed $text, int|string $letter): string => (string) $letter.' '.(string) $text)
                ->implode("\n");

            $blocks[] = "INFO TEXTS:\n".$infoTexts;
        }

        if (is_array($payload['correct'] ?? null) && $payload['correct'] !== []) {
            $correct = collect($payload['correct'])
                ->map(function (mixed $answer, int|string $key): string {
                    if (is_string($key) && preg_match('/situation_(\d+)/', $key, $matches) === 1) {
                        return $matches[1].' '.(string) $answer;
                    }

                    return (string) $key.' '.(string) $answer;
                })
                ->implode("\n");

            $blocks[] = "CORRECT:\n".$correct;
        }

        return $blocks === [] ? 'No structured preview available.' : implode("\n\n", $blocks);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected static function formatReadingMatchingHeadlinesExercise(array $payload): string
    {
        $blocks = [];

        if (filled($payload['instructions'] ?? null)) {
            $blocks[] = "INSTRUCTIONS:\n".$payload['instructions'];
        }

        if (is_array($payload['headings'] ?? null) && $payload['headings'] !== []) {
            $headings = collect($payload['headings'])
                ->map(fn (mixed $heading, int|string $key): string => static::formatLetteredOrIndexedItem($key, (string) $heading))
                ->implode("\n");

            $blocks[] = "HEADINGS:\n".$headings;
        }

        if (is_array($payload['texts'] ?? null) && $payload['texts'] !== []) {
            $texts = collect($payload['texts'])
                ->map(function (mixed $text, int|string $key): string {
                    if (! is_array($text)) {
                        return static::formatLetteredOrIndexedItem($key, (string) $text);
                    }

                    $label = array_key_exists('number', $text)
                        ? (string) $text['number']
                        : ($text['label'] ?? $text['id'] ?? $key);
                    $value = trim((string) ($text['text'] ?? $text['value'] ?? ''));

                    return static::formatLetteredOrIndexedItem($label, $value);
                })
                ->implode("\n\n");

            $blocks[] = "TEXTS:\n".$texts;
        }

        if (is_array($payload['correct'] ?? null) && $payload['correct'] !== []) {
            $correct = static::formatAnswerMap($payload['correct']);
            $blocks[] = "CORRECT:\n".$correct;
        }

        return $blocks === [] ? 'No structured preview available.' : implode("\n\n", $blocks);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected static function formatReadingArticleMcExercise(array $payload): string
    {
        $blocks = [];

        if (filled($payload['instructions'] ?? null)) {
            $blocks[] = "INSTRUCTIONS:\n".$payload['instructions'];
        }

        if (filled($payload['article_title'] ?? null)) {
            $blocks[] = "ARTICLE TITLE:\n".$payload['article_title'];
        }

        if (is_array($payload['articles'] ?? null) && $payload['articles'] !== []) {
            $articles = collect($payload['articles'])
                ->map(function (mixed $article, int|string $key): string {
                    if (! is_array($article)) {
                        return static::formatListItem($key, (string) $article);
                    }

                    $label = trim((string) ($article['title'] ?? $article['label'] ?? $article['id'] ?? $key));
                    $body = trim((string) ($article['body'] ?? $article['text'] ?? $article['value'] ?? ''));

                    return trim($label.($body !== '' ? "\n".$body : ''));
                })
                ->implode("\n\n");

            $blocks[] = "ARTICLES:\n".$articles;
        }

        if (($payload['articles'] ?? []) === [] && filled($payload['article_text'] ?? null)) {
            $blocks[] = "ARTICLE TEXT:\n".$payload['article_text'];
        }

        if (is_array($payload['questions'] ?? null) && $payload['questions'] !== []) {
            $questions = collect($payload['questions'])
                ->map(function (mixed $question, int|string $key): string {
                    if (! is_array($question)) {
                        return static::formatListItem($key, (string) $question);
                    }

                    $prompt = trim((string) ($question['prompt'] ?? $question['question'] ?? ''));
                    $options = collect((array) ($question['options'] ?? []))
                        ->map(function (mixed $option, int|string $optionKey): string {
                            if (! is_array($option)) {
                                return static::formatLetteredOrIndexedItem($optionKey, (string) $option);
                            }

                            $label = $option['label'] ?? $option['id'] ?? $optionKey;
                            $value = trim((string) ($option['text'] ?? $option['value'] ?? ''));

                            return static::formatLetteredOrIndexedItem($label, $value);
                        })
                        ->implode("\n");

                    $questionLabel = array_key_exists('number', $question)
                        ? (string) $question['number']
                        : $key;

                    return trim(static::formatListItem($questionLabel, $prompt).($options !== '' ? "\n".$options : ''));
                })
                ->implode("\n\n");

            $blocks[] = "QUESTIONS:\n".$questions;
        }

        if (is_array($payload['correct'] ?? null) && $payload['correct'] !== []) {
            $blocks[] = "CORRECT:\n".static::formatAnswerMap($payload['correct']);
        }

        return $blocks === [] ? 'No structured preview available.' : implode("\n\n", $blocks);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected static function formatSprachbausteinePerGapExercise(array $payload): string
    {
        $blocks = [];

        if (filled($payload['instructions'] ?? null)) {
            $blocks[] = "INSTRUCTIONS:\n".$payload['instructions'];
        }

        if (filled($payload['text_with_gaps'] ?? null)) {
            $blocks[] = "TEXT WITH GAPS:\n".$payload['text_with_gaps'];
        }

        if (is_array($payload['options_per_gap'] ?? null) && $payload['options_per_gap'] !== []) {
            $options = collect($payload['options_per_gap'])
                ->map(function (mixed $optionSet, int|string $key): string {
                    $label = is_int($key) ? (string) ($key + 1) : (string) $key;

                    if (! is_array($optionSet)) {
                        return $label.' '.(string) $optionSet;
                    }

                    $formatted = collect($optionSet)
                        ->map(fn (mixed $option, int|string $optionKey): string => static::formatLetteredOrIndexedItem($optionKey, (string) $option))
                        ->implode("\n");

                    return "Gap {$label}:\n".$formatted;
                })
                ->implode("\n\n");

            $blocks[] = "OPTIONS PER GAP:\n".$options;
        }

        if (is_array($payload['correct'] ?? null) && $payload['correct'] !== []) {
            $blocks[] = "CORRECT:\n".static::formatAnswerMap($payload['correct']);
        }

        return $blocks === [] ? 'No structured preview available.' : implode("\n\n", $blocks);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected static function formatSprachbausteineSharedPoolExercise(array $payload): string
    {
        $blocks = [];

        if (filled($payload['instructions'] ?? null)) {
            $blocks[] = "INSTRUCTIONS:\n".$payload['instructions'];
        }

        if (filled($payload['text_with_gaps'] ?? null)) {
            $blocks[] = "TEXT WITH GAPS:\n".$payload['text_with_gaps'];
        }

        if (is_array($payload['options_pool'] ?? null) && $payload['options_pool'] !== []) {
            $pool = collect($payload['options_pool'])
                ->map(fn (mixed $option, int|string $key): string => static::formatLetteredOrIndexedItem($key, (string) $option))
                ->implode("\n");

            $blocks[] = "OPTIONS POOL:\n".$pool;
        }

        if (is_array($payload['correct'] ?? null) && $payload['correct'] !== []) {
            $blocks[] = "CORRECT:\n".static::formatAnswerMap($payload['correct']);
        }

        return $blocks === [] ? 'No structured preview available.' : implode("\n\n", $blocks);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected static function formatListeningExercise(array $payload): string
    {
        return static::formatGenericExercise($payload, [
            'instructions',
            'intro',
            'segments',
            'statements',
            'transcript',
            'correct',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    protected static function formatGenericExercise(array $payload, array $keys = [
        'instructions',
        'article_text',
        'text_with_gaps',
        'intro',
        'transcript',
        'situations',
        'questions',
        'headings',
        'texts',
        'info_texts',
        'options',
        'options_pool',
        'statements',
        'segments',
        'correct',
    ]): string
    {
        $blocks = collect($keys)
            ->map(function (string $key) use ($payload): ?string {
                $value = $payload[$key] ?? null;

                if (! filled($value)) {
                    return null;
                }

                return match (true) {
                    is_array($value) => strtoupper(str_replace('_', ' ', $key)).":\n".static::formatStructuredValue($value),
                    default => strtoupper(str_replace('_', ' ', $key)).":\n".(string) $value,
                };
            })
            ->filter()
            ->values()
            ->all();

        return $blocks === [] ? 'No structured preview available.' : implode("\n\n", $blocks);
    }

    /**
     * @param  array<int|string, mixed>  $answers
     */
    protected static function formatAnswerMap(array $answers): string
    {
        return collect($answers)
            ->map(function (mixed $answer, int|string $key): string {
                if (is_string($key) && preg_match('/(?:situation|question|gap|text)_(\d+)/', $key, $matches) === 1) {
                    return $matches[1].' '.(string) $answer;
                }

                return (string) $key.' '.(string) $answer;
            })
            ->implode("\n");
    }

    protected static function formatLetteredOrIndexedItem(int|string $key, string $value): string
    {
        if (is_string($key) && preg_match('/^[A-Z]$/', $key) === 1) {
            return $key.' '.$value;
        }

        return static::formatListItem($key, $value);
    }

    protected static function formatListItem(int|string $key, string $value): string
    {
        $prefix = is_int($key) ? ($key + 1).'.' : (string) $key;

        return "{$prefix} {$value}";
    }

    public static function renderSourcePagePreviews(?ExamExample $record): string
    {
        if (! $record instanceof ExamExample || ! $record->relationLoaded('source') || ! $record->source instanceof ExamExampleSource) {
            return 'No source preview available.';
        }

        $pageFrom = (int) ($record->source_page_from ?? 0);
        $pageTo = (int) ($record->source_page_to ?? 0);

        if ($pageFrom < 1 || $pageTo < $pageFrom) {
            return $record->source_type === 'internal_curated'
                ? 'Curated without source pages.'
                : 'Source pages not backfilled yet.';
        }

        $previewDirectories = static::resolvePreviewDirectories($record->source);

        if ($previewDirectories === []) {
            return "Source pages {$pageFrom}-{$pageTo} are configured, but no preview directory is available.";
        }

        $images = [];
        $missingPages = [];
        $resolvedDirectory = null;

        foreach ($previewDirectories as $previewDirectory) {
            $candidateImages = [];
            $candidateMissingPages = [];

            for ($page = $pageFrom; $page <= $pageTo; $page++) {
                $relativePath = "{$previewDirectory}/page-{$page}.png";
                $absolutePath = public_path($relativePath);

                if (! File::exists($absolutePath)) {
                    $candidateMissingPages[] = $page;

                    continue;
                }

                $url = asset($relativePath);

                $candidateImages[] = <<<HTML
<figure class="space-y-2">
    <figcaption class="text-xs text-gray-400">Page {$page}</figcaption>
    <img src="{$url}" alt="Source page {$page}" class="w-full rounded-lg border border-gray-800" loading="lazy">
</figure>
HTML;
            }

            if ($candidateImages === []) {
                continue;
            }

            $images = $candidateImages;
            $missingPages = $candidateMissingPages;
            $resolvedDirectory = $previewDirectory;

            if ($candidateMissingPages === []) {
                break;
            }
        }

        if ($images === []) {
            return "Source pages {$pageFrom}-{$pageTo} are configured, but no rendered PNG previews were found.";
        }

        $diagnostic = '';

        if ($missingPages !== []) {
            $diagnostic = '<p class="text-xs text-amber-300">Missing rendered pages: '.implode(', ', $missingPages).'.</p>';
        } elseif ($resolvedDirectory !== null) {
            $diagnostic = '<p class="text-xs text-gray-400">Preview source: '.e($resolvedDirectory).'</p>';
        }

        return '<div class="space-y-3">'.$diagnostic.'<div class="space-y-6">'.implode('', $images).'</div></div>';
    }

    /**
     * @return list<string>
     */
    protected static function resolvePreviewDirectories(ExamExampleSource $source): array
    {
        $configuredDirectories = array_values(array_filter(array_map(
            static fn (mixed $directory): string => trim((string) $directory),
            array_merge(
                (array) data_get($source->metadata, 'preview_directories', []),
                [data_get($source->metadata, 'preview_directory', '')],
            ),
        )));

        $resolved = collect($configuredDirectories)
            ->map(function (string $directory): ?string {
                if ($directory === '') {
                    return null;
                }

                $relativeDirectory = Str::of($directory)
                    ->ltrim('/')
                    ->replaceStart('public/', '')
                    ->toString();

                return File::isDirectory(public_path($relativeDirectory))
                    ? $relativeDirectory
                    : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($resolved !== []) {
            return $resolved;
        }

        return collect($configuredDirectories)
            ->map(function (string $directory): ?string {
                if ($directory === '') {
                    return null;
                }

                $relativeDirectory = Str::of($directory)
                    ->ltrim('/')
                    ->replaceStart('public/', '')
                    ->toString();

                $fullRelativeDirectory = Str::endsWith($relativeDirectory, '-full')
                    ? $relativeDirectory
                    : "{$relativeDirectory}-full";

                return File::isDirectory(public_path($fullRelativeDirectory))
                    ? $fullRelativeDirectory
                    : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
