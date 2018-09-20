<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest;

return [

    'auth' => [
        'routes_whitelist' => [
            Action\AuthenticateAction::class,
            Action\ShortUrl\SingleStepCreateShortUrlAction::class,
        ],
    ],

];
