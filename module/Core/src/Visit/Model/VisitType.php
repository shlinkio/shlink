<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

enum VisitType: string
{
    case VALID_SHORT_URL = 'valid_short_url';
    case IMPORTED = 'imported';
    case INVALID_SHORT_URL = 'invalid_short_url';
    case BASE_URL = 'base_url';
    case REGULAR_404 = 'regular_404';
}
