<?php

namespace App\Filament\Resources\ExamExamples\Pages;

use App\Filament\Resources\ExamExamples\ExamExampleResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewExamExample extends ViewRecord
{
    protected static string $resource = ExamExampleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
