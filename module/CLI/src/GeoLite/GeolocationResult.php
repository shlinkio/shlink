<?php

namespace Shlinkio\Shlink\CLI\GeoLite;

enum GeolocationResult
{
    case CHECK_SKIPPED;
    case DB_CREATED;
    case DB_UPDATED;
    case DB_IS_UP_TO_DATE;
}
