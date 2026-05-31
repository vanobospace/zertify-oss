<?php

use App\Services\GeminiService;

it('counts words in plain text', function (): void {
    expect(GeminiService::countTextWords('Hallo Welt das ist ein Test'))->toBe(6);
});

it('ignores gap markers when counting words', function (): void {
    $text = 'Sehr geehrte Damen und Herren, {{gap_1}} freue ich mich {{gap_2}} Ihre Nachricht.';
    expect(GeminiService::countTextWords($text))->toBe(10);
});

it('counts all ten gap markers removed correctly', function (): void {
    $markers = implode(' ', array_map(fn ($i) => "{{gap_{$i}}}", range(1, 10)));
    $text = "Wort1 Wort2 {$markers} Wort3";
    expect(GeminiService::countTextWords($text))->toBe(3);
});

it('returns zero for empty string', function (): void {
    expect(GeminiService::countTextWords(''))->toBe(0);
});

it('counts unicode letters correctly', function (): void {
    expect(GeminiService::countTextWords('Schöne Grüße aus München'))->toBe(4);
});
