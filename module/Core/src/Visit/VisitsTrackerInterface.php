<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;

interface VisitsTrackerInterface
{
    public function track(ShortUrl $shortUrl, Visitor $visitor): void;

    public function trackInvalidShortUrlVisit(Visitor $visitor): void;

    public function trackBaseUrlVisit(Visitor $visitor): void;

    public function trackRegularNotFoundVisit(Visitor $visitor): void;
}
