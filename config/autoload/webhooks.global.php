<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

// Deprecated. Webhooks are no longer supported. To be removed in Shlink 4.0.0
return (static function (): array {
    $webhooks = EnvVars::VISITS_WEBHOOKS->loadFromEnv();

    return [

        'visits_webhooks' => [
            'webhooks' => $webhooks === null ? [] : explode(',', $webhooks),
            'notify_orphan_visits_to_webhooks' =>
                (bool) EnvVars::NOTIFY_ORPHAN_VISITS_TO_WEBHOOKS->loadFromEnv(false),
        ],

    ];
})();
