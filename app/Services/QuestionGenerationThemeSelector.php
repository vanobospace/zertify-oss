<?php

namespace App\Services;

use App\Models\Module;
use App\Models\QuestionGenerationTheme;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class QuestionGenerationThemeSelector
{
    public function selectForModule(Module $module): QuestionGenerationTheme
    {
        $module->loadMissing('exam');

        $themes = QuestionGenerationTheme::query()
            ->where('exam_slug', $module->exam?->slug ?? '')
            ->where('module_slug', $module->slug)
            ->where('is_active', true)
            ->where('status', QuestionGenerationTheme::STATUS_APPROVED)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($themes->isEmpty()) {
            throw new RuntimeException("No active generation themes found for module [{$module->slug}].");
        }

        if ($themes->count() === 1) {
            return $themes->first();
        }

        $cursorKey = $this->cursorKey($module);
        $previousIndex = (int) Cache::get($cursorKey, -1);
        $nextIndex = $previousIndex + 1;

        if ($nextIndex >= $themes->count() || $nextIndex < 0) {
            $nextIndex = 0;
        }

        Cache::forever($cursorKey, $nextIndex);

        return $themes->get($nextIndex);
    }

    private function cursorKey(Module $module): string
    {
        $examSlug = (string) ($module->exam?->slug ?? 'no-exam');

        return sprintf('question-generation-theme-cursor:%s:%s', $examSlug, $module->slug);
    }
}
