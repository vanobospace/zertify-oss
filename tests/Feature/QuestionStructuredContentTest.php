<?php

use App\Support\QuestionStructuredContent;

it('converts listening content into structured editor state and back', function () {
    $content = [
        'format' => 'listening_long_true_false',
        'instructions' => 'Hören Sie das Interview.',
        'audio' => [
            'title' => 'Interview',
            'url' => 'http://zertify.test/storage/question-audio/demo.wav',
            'audio_notes' => 'Ruhig gesprochen.',
        ],
        'transcript' => 'Moderator: Willkommen.',
        'context' => [
            'speaker' => 'Moderator und Gast',
            'replay_limit' => 1,
        ],
        'statements' => [
            ['id' => 'statement_1', 'number' => 1, 'text' => 'Aussage 1'],
            ['id' => 'statement_2', 'number' => 2, 'text' => 'Aussage 2'],
        ],
        'correct' => [
            'statement_1' => 'true',
            'statement_2' => 'false',
        ],
        'explanation' => [
            'statement_1' => [
                'correct_answer' => 'true',
                'reason' => 'Passt.',
                'evidence' => 'Direkt gesagt.',
                'wrong_answer_reason' => 'Das Gegenteil wurde nicht gesagt.',
                'strategy_hint' => 'Auf Zeitangaben achten.',
            ],
            'statement_2' => [
                'correct_answer' => 'false',
                'reason' => 'Stimmt nicht.',
                'evidence' => 'Anderer Inhalt.',
                'wrong_answer_reason' => 'Die Aussage verdreht den Sinn.',
                'strategy_hint' => '',
            ],
        ],
    ];

    $structured = QuestionStructuredContent::toStructured($content, 'listening_long_true_false');

    expect($structured['audio_title'])->toBe('Interview')
        ->and($structured['speaker'])->toBe('Moderator und Gast')
        ->and($structured['statements'][0]['correct_answer'])->toBe('true')
        ->and($structured['statements'][0]['evidence'])->toBe('Direkt gesagt.');

    $rebuilt = QuestionStructuredContent::mergeIntoContent($content, $structured, 'listening_long_true_false');

    expect($rebuilt['format'])->toBe('listening_long_true_false')
        ->and($rebuilt['audio']['url'])->toBe('http://zertify.test/storage/question-audio/demo.wav')
        ->and($rebuilt['audio']['audio_notes'])->toBe('Ruhig gesprochen.')
        ->and($rebuilt['context']['speaker'])->toBe('Moderator und Gast')
        ->and($rebuilt['correct']['statement_2'])->toBe('false')
        ->and($rebuilt['explanation']['statement_1']['evidence'])->toBe('Direkt gesagt.');
});

it('converts reading situations content into structured editor state and back', function () {
    $content = [
        'format' => 'reading_situations_matching',
        'instructions' => 'Ordnen Sie zu.',
        'situations' => [
            ['id' => 'situation_1', 'number' => 1, 'text' => 'Braucht Abendkurs'],
        ],
        'texts' => [
            ['id' => 'text_a', 'label' => 'A', 'title' => 'Abendkurs', 'body' => 'Immer abends.'],
            ['id' => 'text_b', 'label' => 'B', 'title' => 'Morgenkurs', 'body' => 'Immer morgens.'],
        ],
        'extra_answer' => [
            'id' => 'x',
            'label' => 'X',
            'text' => 'Kein passender Text',
        ],
        'correct' => [
            'situation_1' => 'text_a',
        ],
        'explanation' => [
            'situation_1' => [
                'correct_answer' => 'text_a',
                'reason' => 'Nur A ist abends.',
                'evidence' => 'Der Text nennt den Abend.',
                'wrong_answer_reason' => 'B ist morgens.',
                'strategy_hint' => 'Auf Tageszeiten achten.',
            ],
        ],
    ];

    $structured = QuestionStructuredContent::toStructured($content, 'reading_situations_matching');

    expect($structured['situations'][0]['correct_answer'])->toBe('text_a')
        ->and($structured['extra_answer_label'])->toBe('X')
        ->and($structured['texts'][0]['label'])->toBe('A');

    $rebuilt = QuestionStructuredContent::mergeIntoContent($content, $structured, 'reading_situations_matching');

    expect($rebuilt['format'])->toBe('reading_situations_matching')
        ->and($rebuilt['extra_answer']['text'])->toBe('Kein passender Text')
        ->and($rebuilt['correct']['situation_1'])->toBe('text_a')
        ->and($rebuilt['explanation']['situation_1']['wrong_answer_reason'])->toBe('B ist morgens.');
});

it('converts segmented listening teil 1 content into structured editor state and back', function () {
    $content = [
        'format' => 'listening_segmented_true_false',
        'instructions' => 'Sie hören nun eine Nachrichtensendung. Dazu sollen Sie fünf Aufgaben lösen. Sie hören die Nachrichtensendung nur einmal.',
        'audio' => [
            'title' => 'Regionalnachrichten am Abend',
            'url' => 'http://zertify.test/storage/question-audio/teil1.wav',
            'audio_notes' => 'Nachrichtensendung mit neutraler Sprecherstimme und fünf klar getrennten Meldungen aus den Regionen.',
        ],
        'intro' => [
            'text' => 'Guten Abend. Sie hören jetzt fünf Meldungen aus Stadt und Region.',
            'voice_profile' => 'anchor_main',
        ],
        'segments' => [
            [
                'id' => 'segment_1',
                'number' => 1,
                'voice_profile' => 'news_main',
                'segment_text' => 'Der Markt findet morgen in der Stadthalle statt.',
                'statement_id' => 'statement_1',
                'statement_text' => 'Der Markt findet in einer Halle statt.',
                'correct_answer' => 'true',
                'reason' => 'Die Stadthalle wird genannt.',
                'evidence' => 'in der Stadthalle',
                'wrong_answer_reason' => 'Draußen wird gerade nicht gesagt.',
                'strategy_hint' => 'Auf Ortsangaben achten.',
            ],
            [
                'id' => 'segment_2',
                'number' => 2,
                'voice_profile' => 'news_main',
                'segment_text' => 'Die Brücke bleibt bis Dienstag geschlossen.',
                'statement_id' => 'statement_2',
                'statement_text' => 'Die Brücke ist schon offen.',
                'correct_answer' => 'false',
                'reason' => 'Sie bleibt geschlossen.',
                'evidence' => 'bis Dienstag geschlossen',
                'wrong_answer_reason' => 'Das Gegenteil wird gesagt.',
                'strategy_hint' => 'Auf Zeitangaben achten.',
            ],
            [
                'id' => 'segment_3',
                'number' => 3,
                'voice_profile' => 'news_main',
                'segment_text' => 'Im Hallenbad beginnt ein zusätzlicher Schwimmkurs.',
                'statement_id' => 'statement_3',
                'statement_text' => 'Es gibt einen neuen Schwimmkurs.',
                'correct_answer' => 'true',
                'reason' => 'Ein zusätzlicher Kurs wird genannt.',
                'evidence' => 'zusätzlicher Schwimmkurs',
                'wrong_answer_reason' => '',
                'strategy_hint' => '',
            ],
            [
                'id' => 'segment_4',
                'number' => 4,
                'voice_profile' => 'news_main',
                'segment_text' => 'Für das Stadtfest werden noch Helfer gesucht.',
                'statement_id' => 'statement_4',
                'statement_text' => 'Es werden keine Helfer mehr gesucht.',
                'correct_answer' => 'false',
                'reason' => 'Es werden noch Helfer gesucht.',
                'evidence' => 'noch Helfer gesucht',
                'wrong_answer_reason' => '',
                'strategy_hint' => '',
            ],
            [
                'id' => 'segment_5',
                'number' => 5,
                'voice_profile' => 'news_main',
                'segment_text' => 'Die Fahrradwerkstatt öffnet erst um zehn Uhr.',
                'statement_id' => 'statement_5',
                'statement_text' => 'Die Werkstatt öffnet später als sonst.',
                'correct_answer' => 'true',
                'reason' => 'Sie öffnet erst um zehn Uhr.',
                'evidence' => 'erst um zehn Uhr',
                'wrong_answer_reason' => '',
                'strategy_hint' => '',
            ],
        ],
    ];

    $structured = QuestionStructuredContent::toStructured($content, 'listening_segmented_true_false');

    expect($structured['audio_title'])->toBe('Regionalnachrichten am Abend')
        ->and($structured['intro_voice_profile'])->toBe('anchor_main')
        ->and($structured['segments'])->toHaveCount(5)
        ->and($structured['segments'][0]['voice_profile'])->toBe('news_main')
        ->and($structured['transcript'])->toContain('Guten Abend. Sie hören jetzt fünf Meldungen aus Stadt und Region.');

    $rebuilt = QuestionStructuredContent::mergeIntoContent($content, $structured, 'listening_segmented_true_false');

    expect($rebuilt['format'])->toBe('listening_segmented_true_false')
        ->and($rebuilt['audio']['url'])->toBe('http://zertify.test/storage/question-audio/teil1.wav')
        ->and($rebuilt['intro']['voice_profile'])->toBe('anchor_main')
        ->and($rebuilt['segments'])->toHaveCount(5)
        ->and($rebuilt['correct']['statement_2'])->toBe('false')
        ->and($rebuilt['explanation']['statement_1']['strategy_hint'])->toBe('Auf Ortsangaben achten.');
});
