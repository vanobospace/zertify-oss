<?php

namespace App\Filament\Resources\QuestionAudioAssets;

use App\Filament\Resources\QuestionAudioAssets\Pages\ManageQuestionAudioAssets;
use App\Models\QuestionAudioAsset;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionAudioAssetResource extends Resource
{
    protected static ?string $model = QuestionAudioAsset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-speaker-wave';

    protected static ?string $navigationLabel = 'Аудио';

    protected static ?string $modelLabel = 'аудио';

    protected static ?string $pluralModelLabel = 'аудио';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('label')
                    ->label('Label')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('path')
                    ->label('Audio file')
                    ->disk('public')
                    ->directory('question-audio')
                    ->acceptedFileTypes([
                        'audio/mpeg',
                        'audio/mp3',
                        'audio/mp4',
                        'audio/x-m4a',
                        'audio/wav',
                        'audio/ogg',
                    ])
                    ->required()
                    ->downloadable()
                    ->openable(),
                Forms\Components\Hidden::make('disk')
                    ->default('public'),
                Forms\Components\TextInput::make('original_name')
                    ->label('Original file name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('duration_seconds')
                    ->label('Duration (seconds)')
                    ->numeric()
                    ->minValue(1),
                Forms\Components\Placeholder::make('transcript_hash')
                    ->label('Transcript hash')
                    ->content(fn (?QuestionAudioAsset $record): string => (string) ($record?->transcript_hash ?: '—'))
                    ->visible(fn (?QuestionAudioAsset $record): bool => $record !== null),
                Forms\Components\Placeholder::make('generated_at')
                    ->label('Generated at')
                    ->content(fn (?QuestionAudioAsset $record): string => $record?->generated_at?->format('d.m.Y H:i:s') ?? '—')
                    ->visible(fn (?QuestionAudioAsset $record): bool => $record !== null),
                Forms\Components\Textarea::make('generation_metadata')
                    ->label('Generation metadata')
                    ->formatStateUsing(static fn (mixed $state): string => is_array($state)
                        ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : '')
                    ->disabled()
                    ->dehydrated(false)
                    ->rows(8)
                    ->columnSpanFull()
                    ->visible(fn (?QuestionAudioAsset $record): bool => $record !== null && is_array($record->generation_metadata)),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Audio')
                    ->searchable(['label', 'original_name', 'path'])
                    ->wrap(),
                Tables\Columns\TextColumn::make('duration_seconds')
                    ->label('Seconds')
                    ->sortable(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageQuestionAudioAssets::route('/'),
        ];
    }
}
