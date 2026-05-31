<?php

namespace App\Filament\Resources\QuestionAudioAssets\Pages;

use App\Filament\Resources\QuestionAudioAssets\QuestionAudioAssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageQuestionAudioAssets extends ManageRecords
{
    protected static string $resource = QuestionAudioAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
