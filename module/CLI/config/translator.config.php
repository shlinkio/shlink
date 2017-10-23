<?php
declare(strict_types=1);

return [

    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../lang',
                'pattern' => '%s.mo',
            ],
        ],
    ],

];
