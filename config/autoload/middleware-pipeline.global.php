<?php
use Zend\Expressive\Container\ApplicationFactory;

return [

    'middleware_pipeline' => [
        'routing' => [
            'middleware' => [
                ApplicationFactory::ROUTING_MIDDLEWARE,
            ],
            'priority' => 10,
        ],

        'post-routing' => [
            'middleware' => [
                ApplicationFactory::DISPATCH_MIDDLEWARE,
            ],
            'priority' => 1,
        ],
    ],
];
