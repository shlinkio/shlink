<?php

namespace Shlinkio\Shlink\Core\Geolocation;

enum GeolocationResult
{
    case CHECK_SKIPPED;
    case LICENSE_MISSING;
    case DB_CREATED;
    case DB_UPDATED;
    case DB_IS_UP_TO_DATE;
}
