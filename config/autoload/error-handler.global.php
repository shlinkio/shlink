<?php

declare(strict_types=1);

use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Shlinkio\Shlink\Common\Logger;
use Shlinkio\Shlink\Core\ErrorHandler\ErrorTemplateResponseGeneratorDelegator;

use function Shlinkio\Shlink\Core\toProblemDetailsType;

return [

    'problem-details' => [
        'default_types_map' => [
            404 => toProblemDetailsType('not-found'),
            500 => toProblemDetailsType('internal-server-error'),
        ],
    ],

    'error_handler' => [
        'listeners' => [Logger\ErrorLogger::class],
    ],

    'dependencies' => [
        'delegators' => [
            ErrorHandler::class => [
                Logger\ErrorHandlerListenerAttachingDelegator::class,
                ErrorTemplateResponseGeneratorDelegator::class,
            ],
            ProblemDetailsMiddleware::class => [
                Logger\ErrorHandlerListenerAttachingDelegator::class,
            ],
        ],
    ],

];
