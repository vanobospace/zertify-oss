<?php

test('german ui copy keeps umlauts in key listening and navigation labels', function () {
    $translations = file_get_contents(base_path('resources/js/composables/usePublicLocale.ts'));
    $dashboard = file_get_contents(base_path('resources/js/Pages/Dashboard.vue'));

    expect($translations)
        ->toContain("'auth.common.back_home': 'Zurück zur Startseite'")
        ->toContain("'dashboard.module.listening.title': 'Hören'")
        ->toContain("'auth.layout.module_value': 'Lesen, Hören, Sprachbausteine'")
        ->not->toContain("'auth.common.back_home': 'Zuruck zur Startseite'")
        ->not->toContain("'dashboard.module.listening.title': 'Horen'");

    expect($dashboard)
        ->toContain('B2-Prüfung')
        ->toContain('Hören')
        ->not->toContain('B2-Prufung')
        ->not->toContain('Horen');
});

test('listening editor templates keep umlauts in german copy', function () {
    $questionResource = file_get_contents(base_path('app/Filament/Resources/QuestionResource.php'));
    $listeningResource = file_get_contents(base_path('app/Filament/Resources/ListeningQuestionResource.php'));

    expect($questionResource)
        ->toContain('"instructions": "Hören Sie zu und markieren Sie richtig oder falsch."')
        ->toContain('"instructions": "Hören Sie das Interview und markieren Sie richtig oder falsch."')
        ->toContain('"transcript": "Moderatorin: Willkommen im Studio. Gast: Vielen Dank für die Einladung. ..."')
        ->not->toContain('"instructions": "Horen Sie zu und markieren Sie richtig oder falsch."')
        ->not->toContain('"instructions": "Horen Sie das Interview und markieren Sie richtig oder falsch."');

    expect($listeningResource)
        ->toContain("protected static ?string \$modelLabel = 'задание hören';")
        ->toContain("protected static ?string \$pluralModelLabel = 'задания hören';")
        ->not->toContain("protected static ?string \$modelLabel = 'задание horen';")
        ->not->toContain("protected static ?string \$pluralModelLabel = 'задания horen';");
});
