<?php

namespace App\Filament\Resources\QuestionGenerationThemes\Pages;

use App\Filament\Resources\QuestionGenerationThemes\QuestionGenerationThemeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageQuestionGenerationThemes extends ManageRecords
{
    protected static string $resource = QuestionGenerationThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->successRedirectUrl(QuestionGenerationThemeResource::getUrl('index')),
        ];
    }
}
