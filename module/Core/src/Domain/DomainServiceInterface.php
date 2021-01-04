<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain;

use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface DomainServiceInterface
{
    /**
     * @return DomainItem[]
     */
    public function listDomains(?ApiKey $apiKey = null): array;
}
