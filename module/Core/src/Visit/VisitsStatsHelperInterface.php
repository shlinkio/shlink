<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface VisitsStatsHelperInterface
{
    public function getVisitsStats(?ApiKey $apiKey = null): VisitsStats;

    /**
     * @return Paginator<Visit>
     * @throws ShortUrlNotFoundException
     */
    public function visitsForShortUrl(
        ShortUrlIdentifier $identifier,
        VisitsParams $params,
        ?ApiKey $apiKey = null,
    ): Paginator;

    /**
     * @return Paginator<Visit>
     * @throws TagNotFoundException
     */
    public function visitsForTag(string $tag, VisitsParams $params, ?ApiKey $apiKey = null): Paginator;

    /**
     * @return Paginator<Visit>
     * @throws DomainNotFoundException
     */
    public function visitsForDomain(string $domain, VisitsParams $params, ?ApiKey $apiKey = null): Paginator;

    /**
     * @return Paginator<Visit>
     */
    public function orphanVisits(OrphanVisitsParams $params, ?ApiKey $apiKey = null): Paginator;

    /**
     * @return Paginator<Visit>
     */
    public function nonOrphanVisits(VisitsParams $params, ?ApiKey $apiKey = null): Paginator;
}
