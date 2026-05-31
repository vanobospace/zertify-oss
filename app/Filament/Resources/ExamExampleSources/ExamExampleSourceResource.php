<?php

namespace App\Filament\Resources\ExamExampleSources;

use App\Filament\Resources\ExamExampleSources\Pages\ManageExamExampleSources;
use App\Models\ExamExampleSource;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ExamExampleSourceResource extends Resource
{
    protected static ?string $model = ExamExampleSource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Источники образцов';

    protected static ?string $modelLabel = 'источник образцов';

    protected static ?string $pluralModelLabel = 'источники образцов';

    protected static string|\UnitEnum|null $navigationGroup = 'Банк образцов';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('source_key')
                    ->label('Source key')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('author_or_publisher')
                    ->label('Author / publisher')
                    ->disabled()
                    ->dehydrated(false),
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
                Forms\Components\TextInput::make('source_type')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('language')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('source_path')
                    ->label('Source path')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_canonical_structure_source')
                    ->label('Canonical structure source')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Toggle::make('is_generation_reference')
                    ->label('Generation reference')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Toggle::make('do_not_publish_directly')
                    ->label('Do not publish directly')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadata')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('source_type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_code')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('examples_count')
                    ->label('Examples')
                    ->counts('examples')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_canonical_structure_source')
                    ->label('Canon')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_generation_reference')
                    ->label('Ref')
                    ->boolean(),
                Tables\Columns\TextColumn::make('source_path')
                    ->label('Path')
                    ->formatStateUsing(fn (?string $state): string => Str::limit((string) $state, 70))
                    ->tooltip(fn (?string $state): ?string => $state)
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
                Tables\Filters\SelectFilter::make('source_type')
                    ->options([
                        'official_booklet' => 'official_booklet',
                        'training_book' => 'training_book',
                        'internal_curated' => 'internal_curated',
                    ]),
                Tables\Filters\TernaryFilter::make('is_canonical_structure_source'),
                Tables\Filters\TernaryFilter::make('is_generation_reference'),
            ])
            ->actions([
                EditAction::make()
                    ->label('Смотреть'),
            ])
            ->bulkActions([])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExamExampleSources::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }
}
