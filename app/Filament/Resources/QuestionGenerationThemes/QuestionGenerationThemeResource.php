<?php

namespace App\Filament\Resources\QuestionGenerationThemes;

use App\Filament\Resources\QuestionGenerationThemes\Pages\ManageQuestionGenerationThemes;
use App\Models\QuestionGenerationTheme;
use App\Services\QuestionGenerationThemeDraftService;
use App\Support\AdminQuestionGenerationMessageTranslator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Throwable;

class QuestionGenerationThemeResource extends Resource
{
    protected static ?string $model = QuestionGenerationTheme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'AI темы';

    protected static ?string $modelLabel = 'AI тема';

    protected static ?string $pluralModelLabel = 'AI темы';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('exam_slug')
                    ->label('Exam slug')
                    ->required()
                    ->maxLength(255)
                    ->default('telc-b2'),
                Forms\Components\TextInput::make('module_slug')
                    ->label('Module slug')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('sprachbausteine-teil-1'),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('prompt_seed')
                    ->label('Theme brief')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('source_label')
                    ->label('Source label')
                    ->maxLength(255),
                Forms\Components\TextInput::make('source_url')
                    ->label('Source URL')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('golden_example')
                    ->label('Golden example')
                    ->helperText('Эталонный пример в нужном формате. Передаётся в промпт как стилистический якорь.')
                    ->rows(8)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options(QuestionGenerationTheme::statusOptions())
                    ->default(QuestionGenerationTheme::STATUS_DRAFT)
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exam_slug')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('module_slug')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        QuestionGenerationTheme::STATUS_DRAFT => 'gray',
                        QuestionGenerationTheme::STATUS_REVIEWED => 'warning',
                        QuestionGenerationTheme::STATUS_APPROVED => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('source_label')
                    ->label('Source')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('golden_example')
                    ->label('Example')
                    ->boolean()
                    ->getStateUsing(fn (QuestionGenerationTheme $record): bool => filled($record->golden_example))
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_previewed_at')
                    ->label('Last Preview')
                    ->since()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module_slug')
                    ->options([
                        'sprachbausteine-teil-1' => 'Sprachbausteine Teil 1',
                        'sprachbausteine-teil-2' => 'Sprachbausteine Teil 2',
                        'hoeren-teil-1' => 'Hören Teil 1',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options(QuestionGenerationTheme::statusOptions()),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                Action::make('generate_preview')
                    ->label(__('admin.theme_generation.preview_action'))
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->schema([
                        Forms\Components\Select::make('difficulty')
                            ->options([
                                'easy' => 'Easy',
                                'medium' => 'Medium',
                                'hard' => 'Hard',
                            ])
                            ->default('medium')
                            ->required(),
                    ])
                    ->action(function (QuestionGenerationTheme $record, array $data): void {
                        try {
                            app(QuestionGenerationThemeDraftService::class)->generatePreview(
                                $record,
                                (string) $data['difficulty'],
                            );

                            Notification::make()
                                ->title(__('admin.theme_generation.preview_generated_title'))
                                ->body(__('admin.theme_generation.preview_generated_body'))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title(__('admin.theme_generation.preview_failed_title'))
                                ->body(app(AdminQuestionGenerationMessageTranslator::class)->translateThrowable($e))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('view_preview')
                    ->label(__('admin.theme_generation.view_preview_action'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('admin.theme_generation.close'))
                    ->modalContent(fn (QuestionGenerationTheme $record): View => view(
                        'filament.question-generation-themes.preview',
                        ['theme' => $record],
                    ))
                    ->hidden(fn (QuestionGenerationTheme $record): bool => blank($record->last_preview_payload)),
                Action::make('generate_draft')
                    ->label(__('admin.theme_generation.draft_action'))
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->schema([
                        Forms\Components\Select::make('difficulty')
                            ->options([
                                'easy' => 'Easy',
                                'medium' => 'Medium',
                                'hard' => 'Hard',
                            ])
                            ->default('medium')
                            ->required(),
                    ])
                    ->action(function (QuestionGenerationTheme $record, array $data): void {
                        try {
                            $question = app(QuestionGenerationThemeDraftService::class)->generateDraftQuestion(
                                $record,
                                (string) $data['difficulty'],
                            );

                            Notification::make()
                                ->title(__('admin.theme_generation.draft_created_title', ['id' => $question->id]))
                                ->body(__('admin.theme_generation.draft_created_body'))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title(__('admin.theme_generation.draft_failed_title'))
                                ->body(app(AdminQuestionGenerationMessageTranslator::class)->translateThrowable($e))
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageQuestionGenerationThemes::route('/'),
        ];
    }
}
