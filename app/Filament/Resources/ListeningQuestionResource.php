<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListeningQuestionResource\Pages;
use BackedEnum;

class ListeningQuestionResource extends QuestionResource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-speaker-wave';

    protected static ?string $navigationLabel = 'Гьорен';

    protected static ?string $modelLabel = 'задание hören';

    protected static ?string $pluralModelLabel = 'задания hören';

    /**
     * @return list<string>
     */
    protected static function moduleTypeFilters(): ?array
    {
        return ['listening'];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListeningQuestions::route('/'),
            'create' => Pages\CreateListeningQuestion::route('/create'),
            'edit' => Pages\EditListeningQuestion::route('/{record}/edit'),
        ];
    }
}
