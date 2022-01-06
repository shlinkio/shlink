<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use function Shlinkio\Shlink\Config\env;

return (static function (): array {
    $threshold = env('DELETE_SHORT_URL_THRESHOLD');

    return [

        'delete_short_urls' => [
            'check_visits_threshold' => $threshold !== null,
            'visits_threshold' => (int) ($threshold ?? DEFAULT_DELETE_SHORT_URL_THRESHOLD),
        ],

    ];
})();
