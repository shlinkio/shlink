<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

enum OrphanVisitType: string
{
    case INVALID_SHORT_URL = 'invalid_short_url';
    case BASE_URL = 'base_url';
    case REGULAR_404 = 'regular_404';
    case EXPIRED_SHORT_URL = 'expired_short_url';
}
