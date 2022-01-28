<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface VisitsStatsHelperInterface
{
    public function getVisitsStats(?ApiKey $apiKey = null): VisitsStats;

    /**
     * @return Visit[]|Paginator
     * @throws ShortUrlNotFoundException
     */
    public function visitsForShortUrl(
        ShortUrlIdentifier $identifier,
        VisitsParams $params,
        ?ApiKey $apiKey = null,
    ): Paginator;

    /**
     * @return Visit[]|Paginator
     * @throws TagNotFoundException
     */
    public function visitsForTag(string $tag, VisitsParams $params, ?ApiKey $apiKey = null): Paginator;

    /**
     * @return Visit[]|Paginator
     */
    public function orphanVisits(VisitsParams $params): Paginator;

    /**
     * @return Visit[]|Paginator
     */
    public function nonOrphanVisits(VisitsParams $params, ?ApiKey $apiKey = null): Paginator;
}
