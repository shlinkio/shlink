<?php

declare(strict_types=1);

use Shlinkio\Shlink\Common\Logger;
use Zend\ProblemDetails\ProblemDetailsMiddleware;
use Zend\Stratigility\Middleware\ErrorHandler;

return [

    'problem-details' => [
        'default_types_map' => [
            404 => 'NOT_FOUND',
            500 => 'INTERNAL_SERVER_ERROR',
        ],
    ],

    'backwards_compatible_problem_details' => [
        'json_flags' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
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
