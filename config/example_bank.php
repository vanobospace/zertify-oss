<?php

return [
    'path' => base_path('database/examples/catalog.json'),
    'default_reference_limit' => 3,
    'max_reference_characters' => 4000,
    'exam_defaults' => [
        'telc-b2' => [
            'exam_family' => 'telc',
            'variant' => 'allgemein',
        ],
    ],
];
