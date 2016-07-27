<?php
use Shlinkio\Shlink\Common\Expressive\ContentBasedErrorHandler;
use Zend\Expressive\Container\TemplatedErrorHandlerFactory;
use Zend\Stratigility\FinalHandler;

return [

    'error_handler' => [
        'plugins' => [
            'invokables' => [
                'text/plain' => FinalHandler::class,
            ],
            'factories' => [
                ContentBasedErrorHandler::DEFAULT_CONTENT => TemplatedErrorHandlerFactory::class,
            ],
            'aliases' => [
                'application/xhtml+xml' => ContentBasedErrorHandler::DEFAULT_CONTENT,
            ],
        ],
    ],

];
