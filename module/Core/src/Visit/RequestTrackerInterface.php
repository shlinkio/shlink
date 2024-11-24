<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;

interface RequestTrackerInterface
{
    public function trackIfApplicable(ShortUrl $shortUrl, ServerRequestInterface $request): Visit|null;

    public function trackNotFoundIfApplicable(ServerRequestInterface $request): Visit|null;
}
