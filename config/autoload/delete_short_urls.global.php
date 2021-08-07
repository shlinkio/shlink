<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use function Shlinkio\Shlink\Common\env;

use const Shlinkio\Shlink\Core\DEFAULT_DELETE_SHORT_URL_THRESHOLD;

return [

    'delete_short_urls' => [
        'check_visits_threshold' => true,
        'visits_threshold' => (int) env('DELETE_SHORT_URL_THRESHOLD', DEFAULT_DELETE_SHORT_URL_THRESHOLD),
    ],

];
