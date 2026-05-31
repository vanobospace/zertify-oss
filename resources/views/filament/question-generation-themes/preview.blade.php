@php
    /** @var \App\Models\QuestionGenerationTheme $theme */
    $preview = $theme->last_preview_payload ?? [];
    $generated = $preview['generated'] ?? [];
    $content = is_array($generated['content'] ?? null) ? $generated['content'] : [];
    $explanations = is_array($content['explanation'] ?? null) ? $content['explanation'] : [];
@endphp

<div class="space-y-4 text-sm">
    <div class="grid gap-3 md:grid-cols-2">
        <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
            <div class="text-xs uppercase tracking-wide text-gray-500">Theme</div>
            <div class="mt-1 font-medium">{{ $theme->title }}</div>
            <div class="mt-2 text-xs text-gray-500">{{ $preview['difficulty'] ?? 'n/a' }} · {{ $preview['format'] ?? 'n/a' }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
            <div class="text-xs uppercase tracking-wide text-gray-500">Generated Topic</div>
            <div class="mt-1 font-medium">{{ $generated['topic'] ?? 'No preview generated yet.' }}</div>
            <div class="mt-2 text-xs text-gray-500">{{ $generated['word_count'] ?? 'n/a' }} words</div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
        <div class="text-xs uppercase tracking-wide text-gray-500">Text Preview</div>
        <pre class="mt-2 whitespace-pre-wrap font-mono text-xs leading-6">{{ $content['text'] ?? $content['transcript'] ?? 'No preview generated yet.' }}</pre>
    </div>

    <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
        <div class="text-xs uppercase tracking-wide text-gray-500">Explanations Snapshot</div>
        <pre class="mt-2 whitespace-pre-wrap font-mono text-xs leading-6">{{ json_encode(array_slice($explanations, 0, 2, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>
