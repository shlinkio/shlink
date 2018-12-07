<?php
declare(strict_types=1);

return [

    'zend-expressive-swoole' => [
        'enable_coroutine' => true,

        'swoole-http-server' => [
            'host' => '0.0.0.0',
            'process-name' => 'shlink',
        ],
    ],

];
