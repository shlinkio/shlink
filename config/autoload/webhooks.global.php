<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Config\env;

return (static function (): array {
    $webhooks = env('VISITS_WEBHOOKS');

    return [

        'visits_webhooks' => [
            'webhooks' => $webhooks === null ? [] : explode(',', $webhooks),
            'notify_orphan_visits_to_webhooks' => (bool) env('NOTIFY_ORPHAN_VISITS_TO_WEBHOOKS', false),
        ],

    ];
})();
