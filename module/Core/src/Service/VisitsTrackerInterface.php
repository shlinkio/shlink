<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Zend\Paginator\Paginator;

interface VisitsTrackerInterface
{
    /**
     * Tracks a new visit to provided short code from provided visitor
     */
    public function track(string $shortCode, Visitor $visitor): void;

    /**
     * Returns the visits on certain short code
     *
     * @return Visit[]|Paginator
     * @throws ShortUrlNotFoundException
     */
    public function info(string $shortCode, VisitsParams $params): Paginator;
}
