<?php
return [

    'routes' => [
        [
            'name' => 'cli',
            'path' => '/command-name',
            'middleware' => function ($req, $resp) {

            },
            'allowed_methods' => ['CLI'],
        ],
    ],

];
