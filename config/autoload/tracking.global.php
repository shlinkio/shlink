<?php

declare(strict_types=1);

return [

    'tracking' => [
        // Tells if IP addresses should be anonymized before persisting, to fulfil data protection regulations
        // This applies only if IP address tracking is enabled
        'anonymize_remote_addr' => true,

        // Tells if visits to not-found URLs should be tracked. The disable_tracking option takes precedence
        'track_orphan_visits' => true,

        // A query param that, if provided, will disable tracking of one particular visit. Always takes precedence
        'disable_track_param' => null,

        // If true, visits will not be tracked at all
        'disable_tracking' => false,

        // If true, visits will be tracked, but neither the IP address, nor the location will be resolved
        'disable_ip_tracking' => false,

        // If true, the referrer will not be tracked
        'disable_referrer_tracking' => false,

        // If true, the user agent will not be tracked
        'disable_ua_tracking' => false,
    ],

];
