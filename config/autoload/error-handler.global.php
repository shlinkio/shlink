<?php

declare(strict_types=1);

use Shlinkio\Shlink\Common\Logger;
use Zend\ProblemDetails\ProblemDetailsMiddleware;
use Zend\Stratigility\Middleware\ErrorHandler;

return [

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
