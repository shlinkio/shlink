<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain;

use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface DomainServiceInterface
{
    /**
     * @return DomainItem[]
     */
    public function listDomains(?ApiKey $apiKey = null): array;

    /**
     * @throws DomainNotFoundException
     */
    public function getDomain(string $domainId): Domain;

    /**
     * @throws DomainNotFoundException If the API key is restricted to one domain and a different one is provided
     */
    public function getOrCreate(string $authority, ?ApiKey $apiKey = null): Domain;

    public function findByAuthority(string $authority, ?ApiKey $apiKey = null): ?Domain;

    /**
     * @throws DomainNotFoundException If the API key is restricted to one domain and a different one is provided
     */
    public function configureNotFoundRedirects(
        string $authority,
        NotFoundRedirects $notFoundRedirects,
        ?ApiKey $apiKey = null,
    ): Domain;
}
