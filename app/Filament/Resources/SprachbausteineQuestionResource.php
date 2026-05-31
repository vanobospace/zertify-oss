<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SprachbausteineQuestionResource\Pages;
use BackedEnum;

class SprachbausteineQuestionResource extends QuestionResource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Шпрахбауштайны';

    protected static ?string $modelLabel = 'шпрахбауштайн';

    protected static ?string $pluralModelLabel = 'шпрахбауштайны';

    /**
     * @return list<string>
     */
    protected static function moduleTypeFilters(): ?array
    {
        return ['gap_fill', 'sprachbausteine'];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSprachbausteineQuestions::route('/'),
            'create' => Pages\CreateSprachbausteineQuestion::route('/create'),
            'edit' => Pages\EditSprachbausteineQuestion::route('/{record}/edit'),
        ];
    }
}
