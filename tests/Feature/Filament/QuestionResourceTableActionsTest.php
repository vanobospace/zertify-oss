<?php

use App\Filament\Resources\QuestionResource\Pages\ListQuestions;
use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseMissing;

function authenticateQuestionsAdmin(): User
{
    $user = User::factory()->admin()->create([
        'email' => 'questions-admin@zertify.app',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    test()->actingAs($user);

    return $user;
}

describe('question resource table actions', function () {
    beforeEach(function () {
        authenticateQuestionsAdmin();
    });

    it('shows edit and delete row actions without depending on public preview routes', function () {
        $exam = Exam::factory()->create();
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
        ]);
        $question = Question::factory()->create([
            'module_id' => $module->id,
            'status' => Question::STATUS_PUBLISHED,
            'is_active' => true,
        ]);

        Livewire::test(ListQuestions::class)
            ->assertTableActionExists('edit', record: $question)
            ->assertTableActionExists('delete', record: $question)
            ->assertTableActionDoesNotExist('preview', record: $question);
    });

    it('can delete a question from the row actions', function () {
        $exam = Exam::factory()->create();
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
        ]);
        $question = Question::factory()->create([
            'module_id' => $module->id,
        ]);

        Livewire::test(ListQuestions::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($question))
            ->assertNotified();

        assertDatabaseMissing('questions', [
            'id' => $question->id,
        ]);
    });

    it('can bulk delete selected questions', function () {
        $exam = Exam::factory()->create();
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
        ]);
        $questions = Question::factory()->count(2)->create([
            'module_id' => $module->id,
        ]);

        Livewire::test(ListQuestions::class)
            ->selectTableRecords($questions->pluck('id')->all())
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertNotified();

        $questions->each(fn (Question $question) => assertDatabaseMissing('questions', [
            'id' => $question->id,
        ]));
    });
});
