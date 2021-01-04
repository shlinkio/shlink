<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain;

use Shlinkio\Shlink\Core\Domain\Model\DomainItem;

interface DomainServiceInterface
{
    /**
     * @return DomainItem[]
     */
    public function listDomainsWithout(): array;
}
