<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

enum VisitType: string
{
    case VALID_SHORT_URL = 'valid_short_url';
    case IMPORTED = 'imported';
    case INVALID_SHORT_URL = OrphanVisitType::INVALID_SHORT_URL->value;
    case BASE_URL = OrphanVisitType::BASE_URL->value;
    case REGULAR_404 = OrphanVisitType::REGULAR_404->value;
}
