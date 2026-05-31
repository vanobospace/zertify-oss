<?php

namespace App\Filament\Resources\ExamExamples\Pages;

use App\Filament\Resources\ExamExamples\ExamExampleResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageExamExamples extends ManageRecords
{
    protected static string $resource = ExamExampleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
