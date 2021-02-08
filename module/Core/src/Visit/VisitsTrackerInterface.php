<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\Visitor;

interface VisitsTrackerInterface
{
    public function track(ShortUrl $shortUrl, Visitor $visitor): void;
}
