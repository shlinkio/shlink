<?php

declare(strict_types=1);

use Shlinkio\Shlink\Core\Config\EnvVars;

return [

    'tracking' => [
        // Tells if IP addresses should be anonymized before persisting, to fulfil data protection regulations
        // This applies only if IP address tracking is enabled
        'anonymize_remote_addr' => (bool) EnvVars::ANONYMIZE_REMOTE_ADDR()->loadFromEnv(true),

        // Tells if visits to not-found URLs should be tracked. The disable_tracking option takes precedence
        'track_orphan_visits' => (bool) EnvVars::TRACK_ORPHAN_VISITS()->loadFromEnv(true),

        // A query param that, if provided, will disable tracking of one particular visit. Always takes precedence
        'disable_track_param' => EnvVars::DISABLE_TRACK_PARAM()->loadFromEnv(),

        // If true, visits will not be tracked at all
        'disable_tracking' => (bool) EnvVars::DISABLE_TRACKING()->loadFromEnv(false),

        // If true, visits will be tracked, but neither the IP address, nor the location will be resolved
        'disable_ip_tracking' => (bool) EnvVars::DISABLE_IP_TRACKING()->loadFromEnv(false),

        // If true, the referrer will not be tracked
        'disable_referrer_tracking' => (bool) EnvVars::DISABLE_REFERRER_TRACKING()->loadFromEnv(false),

        // If true, the user agent will not be tracked
        'disable_ua_tracking' => (bool) EnvVars::DISABLE_UA_TRACKING()->loadFromEnv(false),

        // A list of IP addresses, patterns or CIDR blocks from which tracking is disabled by default
        'disable_tracking_from' => EnvVars::DISABLE_TRACKING_FROM()->loadFromEnv(),
    ],

];
