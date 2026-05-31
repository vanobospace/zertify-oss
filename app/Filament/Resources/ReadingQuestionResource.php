<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReadingQuestionResource\Pages;
use BackedEnum;

class ReadingQuestionResource extends QuestionResource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Лезен';

    protected static ?string $modelLabel = 'задание lesen';

    protected static ?string $pluralModelLabel = 'задания lesen';

    /**
     * @return list<string>
     */
    protected static function moduleTypeFilters(): ?array
    {
        return ['reading'];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReadingQuestions::route('/'),
            'create' => Pages\CreateReadingQuestion::route('/create'),
            'edit' => Pages\EditReadingQuestion::route('/{record}/edit'),
        ];
    }
}
