<?php

use App\Models\Exam;
use App\Models\Module;
use App\Models\QuestionGenerationTheme;
use App\Services\QuestionGenerationThemeSelector;
use Illuminate\Support\Facades\Cache;

it('selects generation themes in round-robin order by sort order', function () {
    Cache::flush();

    $exam = Exam::factory()->create([
        'slug' => 'telc-b2',
    ]);
    $module = Module::factory()->create([
        'exam_id' => $exam->id,
        'slug' => 'hoeren-teil-1',
        'type' => 'listening',
    ]);

    $themeB = QuestionGenerationTheme::factory()->create([
        'exam_slug' => $exam->slug,
        'module_slug' => $module->slug,
        'title' => 'Theme B',
        'sort_order' => 20,
        'is_active' => true,
        'status' => QuestionGenerationTheme::STATUS_APPROVED,
    ]);
    $themeA = QuestionGenerationTheme::factory()->create([
        'exam_slug' => $exam->slug,
        'module_slug' => $module->slug,
        'title' => 'Theme A',
        'sort_order' => 10,
        'is_active' => true,
        'status' => QuestionGenerationTheme::STATUS_APPROVED,
    ]);
    $themeC = QuestionGenerationTheme::factory()->create([
        'exam_slug' => $exam->slug,
        'module_slug' => $module->slug,
        'title' => 'Theme C',
        'sort_order' => 30,
        'is_active' => true,
        'status' => QuestionGenerationTheme::STATUS_APPROVED,
    ]);

    expect(app(QuestionGenerationThemeSelector::class)->selectForModule($module)->is($themeA))->toBeTrue()
        ->and(app(QuestionGenerationThemeSelector::class)->selectForModule($module)->is($themeB))->toBeTrue()
        ->and(app(QuestionGenerationThemeSelector::class)->selectForModule($module)->is($themeC))->toBeTrue()
        ->and(app(QuestionGenerationThemeSelector::class)->selectForModule($module)->is($themeA))->toBeTrue();
});

it('ignores inactive or not-approved themes in round-robin selection', function () {
    Cache::flush();

    $exam = Exam::factory()->create([
        'slug' => 'telc-b2',
    ]);
    $module = Module::factory()->create([
        'exam_id' => $exam->id,
        'slug' => 'lesen-teil-1',
        'type' => 'reading',
    ]);

    QuestionGenerationTheme::factory()->create([
        'exam_slug' => $exam->slug,
        'module_slug' => $module->slug,
        'title' => 'Draft Theme',
        'sort_order' => 5,
        'is_active' => true,
        'status' => QuestionGenerationTheme::STATUS_DRAFT,
    ]);
    QuestionGenerationTheme::factory()->create([
        'exam_slug' => $exam->slug,
        'module_slug' => $module->slug,
        'title' => 'Inactive Theme',
        'sort_order' => 7,
        'is_active' => false,
        'status' => QuestionGenerationTheme::STATUS_APPROVED,
    ]);
    $activeTheme = QuestionGenerationTheme::factory()->create([
        'exam_slug' => $exam->slug,
        'module_slug' => $module->slug,
        'title' => 'Active Theme',
        'sort_order' => 10,
        'is_active' => true,
        'status' => QuestionGenerationTheme::STATUS_APPROVED,
    ]);

    expect(app(QuestionGenerationThemeSelector::class)->selectForModule($module)->is($activeTheme))->toBeTrue()
        ->and(app(QuestionGenerationThemeSelector::class)->selectForModule($module)->is($activeTheme))->toBeTrue();
});
