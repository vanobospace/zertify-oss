<?php

test('listening create form explains ai-first then audio-second flow', function () {
    $questionResource = file_get_contents(base_path('app/Filament/Resources/QuestionResource.php'));

    expect($questionResource)
        ->toContain("->label('Итоговый заголовок')")
        ->toContain("->label('Предпрослушивание аудио')")
        ->toContain('Для Hören в черновике заполняется ИИ; потом можно отредактировать.')
        ->toContain('Для черновиков Hören Gemini сначала создаёт заголовок и транскрипт. Аудио генерируется отдельно из сохранённого транскрипта.')
        ->toContain('Нажмите «Сгенерировать через AI», чтобы получить черновик текста для listening.')
        ->toContain('Сгенерируйте аудио отдельно из сохранённого транскрипта.');
});
