<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;

interface VisitsTrackerInterface
{
    public function track(ShortUrl $shortUrl, Visitor $visitor): Visit|null;

    public function trackInvalidShortUrlVisit(Visitor $visitor): Visit|null;

    public function trackBaseUrlVisit(Visitor $visitor): Visit|null;

    public function trackRegularNotFoundVisit(Visitor $visitor): Visit|null;

    public function trackExpiredShortUrlVisit(Visitor $visitor): Visit|null;
}
