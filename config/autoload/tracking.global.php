<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

return [

    'tracking' => [
        // Tells if IP addresses should be anonymized before persisting, to fulfil data protection regulations
        // This applies only if IP address tracking is enabled
        'anonymize_remote_addr' => (bool) env('ANONYMIZE_REMOTE_ADDR', true),

        // Tells if visits to not-found URLs should be tracked. The disable_tracking option takes precedence
        'track_orphan_visits' => (bool) env('TRACK_ORPHAN_VISITS', true),

        // A query param that, if provided, will disable tracking of one particular visit. Always takes precedence
        'disable_track_param' => env('DISABLE_TRACK_PARAM'),

        // If true, visits will not be tracked at all
        'disable_tracking' => (bool) env('DISABLE_TRACKING', false),

        // If true, visits will be tracked, but neither the IP address, nor the location will be resolved
        'disable_ip_tracking' => (bool) env('DISABLE_IP_TRACKING', false),

        // If true, the referrer will not be tracked
        'disable_referrer_tracking' => (bool) env('DISABLE_REFERRER_TRACKING', false),

        // If true, the user agent will not be tracked
        'disable_ua_tracking' => (bool) env('DISABLE_UA_TRACKING', false),
    ],

];
