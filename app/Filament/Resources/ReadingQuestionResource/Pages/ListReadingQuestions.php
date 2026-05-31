<?php

namespace App\Filament\Resources\ReadingQuestionResource\Pages;

use App\Filament\Resources\QuestionResource\Pages\ListQuestions;
use App\Filament\Resources\ReadingQuestionResource;

class ListReadingQuestions extends ListQuestions
{
    protected static string $resource = ReadingQuestionResource::class;
}
