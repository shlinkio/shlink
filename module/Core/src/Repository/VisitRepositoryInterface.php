<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;

interface VisitRepositoryInterface extends ObjectRepository
{
    /**
     * @return Visit[]
     */
    public function findUnlocatedVisits();

    /**
     * @param ShortUrl|int $shortUrl
     * @param DateRange|null $dateRange
     * @return Visit[]
     */
    public function findVisitsByShortUrl($shortUrl, DateRange $dateRange = null);
}
