<?php

use App\Models\Exam;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Exam Model', function () {
    it('can be created with valid attributes', function () {
        $exam = Exam::factory()->create([
            'name' => 'Goethe B2',
            'slug' => 'goethe-b2',
            'level' => 'B2',
            'is_active' => true,
        ]);

        expect($exam)->toBeInstanceOf(Exam::class)
            ->and($exam->name)->toBe('Goethe B2')
            ->and($exam->slug)->toBe('goethe-b2')
            ->and($exam->level)->toBe('B2')
            ->and($exam->is_active)->toBeTrue();
    });

    it('has many modules', function () {
        $exam = Exam::factory()->create();
        Module::factory()->count(3)->create(['exam_id' => $exam->id]);

        expect($exam->modules)->toHaveCount(3)
            ->and($exam->modules->first())->toBeInstanceOf(Module::class);
    });

    it('can be created as inactive using factory state', function () {
        $exam = Exam::factory()->inactive()->create();

        expect($exam->is_active)->toBeFalse();
    });
});
