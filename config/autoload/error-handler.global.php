<?php

declare(strict_types=1);

use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Shlinkio\Shlink\Common\Logger;

return [

    'problem-details' => [
        'default_types_map' => [
            404 => 'NOT_FOUND', // TODO Define new values, with backwards compatibility if possible
            500 => 'INTERNAL_SERVER_ERROR', // TODO Define new values, with backwards compatibility if possible
        ],
    ],

    'error_handler' => [
        'listeners' => [Logger\ErrorLogger::class],
    ],

    'dependencies' => [
        'delegators' => [
            ErrorHandler::class => [
                Logger\ErrorHandlerListenerAttachingDelegator::class,
            ],
            ProblemDetailsMiddleware::class => [
                Logger\ErrorHandlerListenerAttachingDelegator::class,
            ],
        ],
    ],

];
