<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Module;

class ExamExampleShapeResolver
{
    public function examFamilyForSlug(string $examSlug): string
    {
        $default = (array) config("example_bank.exam_defaults.{$examSlug}", []);

        if (filled($default['exam_family'] ?? null)) {
            return (string) $default['exam_family'];
        }

        return (string) str($examSlug)->before('-');
    }

    public function variantForSlug(string $examSlug): ?string
    {
        $default = (array) config("example_bank.exam_defaults.{$examSlug}", []);

        if (filled($default['variant'] ?? null)) {
            return (string) $default['variant'];
        }

        return null;
    }

    public function partKeyForModuleSlug(string $moduleSlug): ?string
    {
        if (preg_match('/(teil-\d+)$/', $moduleSlug, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public function taskShapeForModuleSlug(string $moduleSlug): string
    {
        return match ($moduleSlug) {
            'lesen-teil-1' => 'reading_matching_headlines',
            'lesen-teil-2' => 'reading_article_mc',
            'lesen-teil-3' => 'reading_situations_matching',
            'sprachbausteine-teil-1' => 'sprachbausteine_per_gap',
            'sprachbausteine-teil-2' => 'sprachbausteine_shared_pool',
            'hoeren-teil-1' => 'listening_segmented_true_false',
            'hoeren-teil-2' => 'listening_long_true_false',
            'hoeren-teil-3' => 'listening_short_true_false',
            default => 'generic_reference',
        };
    }

    /**
     * @return array{
     *   exam_family:string,
     *   exam_code:string,
     *   variant:?string,
     *   level:?string,
     *   module_slug:string,
     *   part_key:?string,
     *   task_shape:string
     * }
     */
    public function constraintsForModule(Module $module): array
    {
        $examSlug = (string) $module->exam?->slug;

        return [
            'exam_family' => $this->examFamilyForSlug($examSlug),
            'exam_code' => $examSlug,
            'variant' => $this->variantForSlug($examSlug),
            'level' => $module->exam?->level,
            'module_slug' => (string) $module->slug,
            'part_key' => $this->partKeyForModuleSlug((string) $module->slug),
            'task_shape' => $this->taskShapeForModuleSlug((string) $module->slug),
        ];
    }

    /**
     * @return array{
     *   exam_family:string,
     *   exam_code:string,
     *   variant:?string,
     *   level:?string
     * }
     */
    public function defaultsForExam(Exam $exam): array
    {
        return [
            'exam_family' => $this->examFamilyForSlug((string) $exam->slug),
            'exam_code' => (string) $exam->slug,
            'variant' => $this->variantForSlug((string) $exam->slug),
            'level' => $exam->level,
        ];
    }
}
