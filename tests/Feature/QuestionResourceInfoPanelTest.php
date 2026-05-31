<?php

use App\Filament\Resources\QuestionResource\Pages\CreateQuestion;
use App\Models\Exam;
use App\Models\Module;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

function authenticateQuestionInfoAdmin(): User
{
    $user = User::factory()->admin()->create([
        'email' => 'info-panel-admin@zertify.app',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    test()->actingAs($user);

    return $user;
}

beforeEach(function () {
    authenticateQuestionInfoAdmin();
});

it('shows a neutral prompt before a module is selected', function () {
    Livewire::test(CreateQuestion::class)
        ->assertSee('Сначала выберите модуль')
        ->assertSee('Сначала выберите модуль, чтобы увидеть правила и статус текущего JSON.');
});

it('shows per-gap guidance when teil 1 is selected', function () {
    $exam = Exam::factory()->create([
        'slug' => 'telc-b2',
    ]);
    $module = Module::factory()->gapFill()->create([
        'exam_id' => $exam->id,
        'slug' => 'sprachbausteine-teil-1',
        'name' => 'Sprachbausteine Teil 1',
    ]);

    Livewire::test(CreateQuestion::class)
        ->fillForm([
            'module_id' => $module->id,
        ])
        ->assertSee('Per-Gap (Teil 1: 3 варианта на каждый пропуск)')
        ->assertSee('Письмо / E-Mail')
        ->assertSee('Для каждого gap нужны ровно 3 варианта ответа.')
        ->assertSee('"options": {');
});

it('shows shared-pool guidance and status when teil 2 is selected', function () {
    $exam = Exam::factory()->create([
        'slug' => 'telc-b2',
    ]);
    $module = Module::factory()->gapFill()->create([
        'exam_id' => $exam->id,
        'slug' => 'sprachbausteine-teil-2',
        'name' => 'Sprachbausteine Teil 2',
    ]);
    $content = json_encode([
        'format' => 'shared_pool',
        'text' => implode("\n\n", [
            'Absatz eins mit {{gap_1}}, {{gap_2}} und {{gap_3}}.',
            'Absatz zwei mit {{gap_4}}, {{gap_5}}, {{gap_6}} und {{gap_7}}.',
            'Absatz drei mit {{gap_8}}, {{gap_9}} und {{gap_10}}.',
        ]),
        'options_pool' => ['dass', 'ob', 'weil', 'jedoch', 'daher', 'obwohl', 'wenn', 'denn', 'indem', 'wobei', 'als', 'sondern', 'doch', 'waehrend', 'falls'],
        'correct' => [
            'gap_1' => 'dass',
            'gap_2' => 'ob',
            'gap_3' => 'weil',
            'gap_4' => 'jedoch',
            'gap_5' => 'daher',
            'gap_6' => 'obwohl',
            'gap_7' => 'wenn',
            'gap_8' => 'denn',
            'gap_9' => 'indem',
            'gap_10' => 'als',
        ],
        'explanation' => [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    Livewire::test(CreateQuestion::class)
        ->fillForm([
            'module_id' => $module->id,
            'content' => $content,
        ])
        ->assertSee('Shared Pool (Teil 2: общий пул из 15 вариантов)')
        ->assertSee('Статья / Sachtext')
        ->assertSee('В content.format должно быть "shared_pool".')
        ->assertSee('[OK] JSON валиден.')
        ->assertSee('Пул вариантов: 15 / 15')
        ->assertSee('[OK] Все correct answers есть в options_pool.');
});
