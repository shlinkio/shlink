<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

return [

    'not_found_redirects' => [
        'invalid_short_url' => env('INVALID_SHORT_URL_REDIRECT_TO'),
        'regular_404' => env('REGULAR_404_REDIRECT_TO'),
        'base_url' => env('BASE_URL_REDIRECT_TO'),
    ],

];
