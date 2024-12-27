<?php

namespace Shlinkio\Shlink\Core\Config\Options;

enum ExtraPathMode: string
{
    /** URLs with extra path will not match a short URL */
    case DEFAULT = 'default';
    /** The extra path will be appended to the long URL */
    case APPEND = 'append';
    /** The extra path will be ignored */
    case IGNORE = 'ignore';
}
