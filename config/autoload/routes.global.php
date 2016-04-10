<?php

return [

    'routes' => [
        [
            'name' => 'home',
            'path' => '/',
            'middleware' => function ($req, $resp) {
                $resp->getBody()->write('Hello world');
                return $resp;
            },
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'cli',
            'path' => 'command-name',
            'middleware' => function ($req, $resp) {
                $resp->getBody()->write('Hello world from cli');
                return $resp;
            },
            'allowed_methods' => ['GET'],
        ],
    ],
    
];
