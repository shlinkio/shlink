<?php

return [

    'routes' => [
        [
            'name' => 'home',
            'path' => '/',
            'middleware' => function ($req, $resp) {

            },
            'allowed_methods' => ['GET'],
        ],
    ],

];
