<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Shlinkio\Shlink\Core\Config\EnvVars;

return (static function (): array {
    $threshold = EnvVars::DELETE_SHORT_URL_THRESHOLD()->loadFromEnv();

    return [

        'delete_short_urls' => [
            'check_visits_threshold' => $threshold !== null,
            'visits_threshold' => (int) ($threshold ?? DEFAULT_DELETE_SHORT_URL_THRESHOLD),
        ],

    ];
})();
