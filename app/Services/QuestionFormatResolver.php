<?php

namespace App\Services;

use App\Models\Module;

class QuestionFormatResolver
{
    public function resolveForModule(Module $module): string
    {
        $slug = (string) $module->slug;

        if (str_starts_with($slug, 'hoeren-teil-')) {
            return match ($slug) {
                'hoeren-teil-1' => 'listening_segmented_true_false',
                'hoeren-teil-2' => 'listening_long_true_false',
                default => 'listening_short_true_false',
            };
        }

        return str_contains($slug, 'teil-2') ? 'shared_pool' : 'per_gap';
    }
}
