<?php

namespace App\Filament\Resources\ReadingQuestionResource\Pages;

use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Filament\Resources\ReadingQuestionResource;

class CreateReadingQuestion extends CreateQuestion
{
    protected static string $resource = ReadingQuestionResource::class;
}
