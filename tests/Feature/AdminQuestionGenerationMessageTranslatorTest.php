<?php

use App\Support\AdminQuestionGenerationMessageTranslator;

it('translates quality check messages for the admin panel into russian', function () {
    app()->setLocale('ru');

    $translator = app(AdminQuestionGenerationMessageTranslator::class);

    $message = $translator->translateMessage(
        "Question generation failed quality checks: Shared-pool options are missing the correct answer 'als'. | Explanation for gap_10 uses a rule_type that does not fit the actual answer."
    );

    expect($message)
        ->toContain('Генерация не прошла проверку качества:')
        ->toContain('В общем пуле вариантов отсутствует правильный ответ «als».')
        ->toContain('Для gap_10 тип правила в explanation не соответствует правильному ответу.');
});

it('builds russian generation notifications with translated warnings', function () {
    app()->setLocale('ru');

    $translator = app(AdminQuestionGenerationMessageTranslator::class);

    $body = $translator->generatedBody('Запрос по аренде квартиры', [
        'Explanation for gap_6 uses a rule_type that does not fit the actual answer.',
    ]);

    expect($body)
        ->toContain('Шаблон темы: Запрос по аренде квартиры.')
        ->toContain('Замечания:')
        ->toContain('Для gap_6 тип правила в explanation не соответствует правильному ответу.');
});

it('builds short review summaries from review gap ids', function () {
    app()->setLocale('ru');

    $translator = app(AdminQuestionGenerationMessageTranslator::class);

    $body = $translator->generatedBody('Klimaschutz im privaten Leben', [
        'Explanation for gap_2 needs a concrete pattern or construction.',
        'Explanation for gap_4 must repeat the exact correct answer.',
    ], ['gap_2', 'gap_4']);

    expect($body)
        ->toContain('Шаблон темы: Klimaschutz im privaten Leben.')
        ->toContain('Проверь explanations для пропусков: 2, 4.')
        ->not->toContain('должен содержать конкретный pattern');
});
