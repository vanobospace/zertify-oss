<?php

namespace App\Services;

interface SpeechSynthesisProvider
{
    public function key(): string;

    /**
     * @param  array<string, mixed>  $options
     * @return array{binary: string, extension: string, mime_type: string, metadata?: array<string, mixed>}
     */
    public function synthesize(string $text, array $options = []): array;
}
