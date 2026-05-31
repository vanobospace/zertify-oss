<?php

use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Module Model', function () {
    it('can be created with valid attributes', function () {
        $exam = Exam::factory()->create();
        $module = Module::factory()->create([
            'exam_id' => $exam->id,
            'name' => 'Sprachbausteine Teil 1',
            'slug' => 'sprachbausteine-teil-1',
            'type' => 'sprachbausteine',
        ]);

        expect($module)->toBeInstanceOf(Module::class)
            ->and($module->name)->toBe('Sprachbausteine Teil 1')
            ->and($module->slug)->toBe('sprachbausteine-teil-1')
            ->and($module->type)->toBe('sprachbausteine');
    });

    it('belongs to an exam', function () {
        $exam = Exam::factory()->create();
        $module = Module::factory()->create(['exam_id' => $exam->id]);

        expect($module->exam)->toBeInstanceOf(Exam::class)
            ->and($module->exam->id)->toBe($exam->id);
    });

    it('has many questions', function () {
        $module = Module::factory()->create();
        Question::factory()->count(5)->create(['module_id' => $module->id]);

        expect($module->questions)->toHaveCount(5)
            ->and($module->questions->first())->toBeInstanceOf(Question::class);
    });
});
