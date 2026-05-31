<?php

namespace App\Filament\Resources\ExamExampleSources\Pages;

use App\Filament\Resources\ExamExampleSources\ExamExampleSourceResource;
use Filament\Resources\Pages\ManageRecords;

class ManageExamExampleSources extends ManageRecords
{
    protected static string $resource = ExamExampleSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
