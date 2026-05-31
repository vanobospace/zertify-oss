<?php

use App\Models\Module;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Question Model', function () {
    it('can be created with valid attributes', function () {
        $module = Module::factory()->create();
        $question = Question::factory()->create([
            'module_id' => $module->id,
            'points' => 3,
            'order' => 1,
        ]);

        expect($question)->toBeInstanceOf(Question::class)
            ->and($question->points)->toBe(3)
            ->and($question->order)->toBe(1);
    });

    it('belongs to a module', function () {
        $module = Module::factory()->create();
        $question = Question::factory()->create(['module_id' => $module->id]);

        expect($question->module)->toBeInstanceOf(Module::class)
            ->and($question->module->id)->toBe($module->id);
    });

    it('casts content to array', function () {
        $question = Question::factory()->create([
            'content' => [
                'text' => 'Test {{gap_1}} question',
                'options' => ['gap_1' => ['a', 'b', 'c']],
                'correct' => ['gap_1' => 'b'],
            ],
        ]);

        $freshQuestion = Question::find($question->id);

        expect($freshQuestion->content)->toBeArray()
            ->and($freshQuestion->content['text'])->toBe('Test {{gap_1}} question')
            ->and($freshQuestion->content['options']['gap_1'])->toBe(['a', 'b', 'c'])
            ->and($freshQuestion->content['correct']['gap_1'])->toBe('b');
    });

    it('casts points and order to integers', function () {
        $question = Question::factory()->create([
            'points' => '5',
            'order' => '2',
        ]);

        expect($question->points)->toBeInt()
            ->and($question->order)->toBeInt();
    });
});
