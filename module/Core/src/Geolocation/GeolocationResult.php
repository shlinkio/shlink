<?php

namespace Shlinkio\Shlink\Core\Geolocation;

enum GeolocationResult
{
    /** Geolocation is not relevant, so updates are skipped */
    case CHECK_SKIPPED;
    /** Update is skipped because max amount of consecutive errors was reached */
    case MAX_ERRORS_REACHED;
    /** Update was skipped because a geolocation license key was not provided */
    case LICENSE_MISSING;
    /** A geolocation database didn't exist and has been created */
    case DB_CREATED;
    /** An outdated geolocation database existed and has been updated */
    case DB_UPDATED;
    /** Geolocation database does not need to be updated yet */
    case DB_IS_UP_TO_DATE;
    /** Geolocation db update is currently in progress */
    case UPDATE_IN_PROGRESS;
}
