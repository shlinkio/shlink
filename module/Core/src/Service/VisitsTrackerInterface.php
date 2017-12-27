<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\Visit;

interface VisitsTrackerInterface
{
    /**
     * Tracks a new visit to provided short code, using an array of data to look up information
     *
     * @param string $shortCode
     * @param ServerRequestInterface $request
     */
    public function track($shortCode, ServerRequestInterface $request);

    /**
     * Returns the visits on certain short code
     *
     * @param string $shortCode
     * @param DateRange $dateRange
     * @return Visit[]
     * @throws InvalidArgumentException
     */
    public function info(string $shortCode, DateRange $dateRange = null): array;
}
