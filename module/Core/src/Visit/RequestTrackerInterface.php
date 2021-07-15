<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;

interface RequestTrackerInterface
{
    public function trackIfApplicable(ShortUrl $shortUrl, ServerRequestInterface $request): void;

    public function trackNotFoundIfApplicable(ServerRequestInterface $request): void;
}
