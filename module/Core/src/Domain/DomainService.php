<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Config\EmptyNotFoundRedirectConfig;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Functional\first;
use function Functional\group;
use function Functional\map;

class DomainService implements DomainServiceInterface
{
    public function __construct(private EntityManagerInterface $em, private string $defaultDomain)
    {
    }

    /**
     * @return DomainItem[]
     */
    public function listDomains(?ApiKey $apiKey = null): array
    {
        [$default, $domains] = $this->defaultDomainAndRest($apiKey);
        $mappedDomains = map($domains, fn (Domain $domain) => DomainItem::forNonDefaultDomain($domain));

        if ($apiKey?->hasRole(Role::DOMAIN_SPECIFIC)) {
            return $mappedDomains;
        }

        return [
            DomainItem::forDefaultDomain($this->defaultDomain, $default ?? new EmptyNotFoundRedirectConfig()),
            ...$mappedDomains,
        ];
    }

    /**
     * @return array{Domain|null, Domain[]}
     */
    private function defaultDomainAndRest(?ApiKey $apiKey): array
    {
        /** @var DomainRepositoryInterface $repo */
        $repo = $this->em->getRepository(Domain::class);
        $groups = group(
            $repo->findDomains($apiKey),
            fn (Domain $domain) => $domain->getAuthority() === $this->defaultDomain ? 'default' : 'domains',
        );

        return [first($groups['default'] ?? []), $groups['domains'] ?? []];
    }

    /**
     * @throws DomainNotFoundException
     */
    public function getDomain(string $domainId): Domain
    {
        /** @var Domain|null $domain */
        $domain = $this->em->find(Domain::class, $domainId);
        if ($domain === null) {
            throw DomainNotFoundException::fromId($domainId);
        }

        return $domain;
    }

    public function findByAuthority(string $authority, ?ApiKey $apiKey = null): ?Domain
    {
        return $this->em->getRepository(Domain::class)->findOneByAuthority($authority, $apiKey);
    }

    /**
     * @throws DomainNotFoundException
     */
    public function getOrCreate(string $authority, ?ApiKey $apiKey = null): Domain
    {
        $domain = $this->getPersistedDomain($authority, $apiKey);
        $this->em->flush();

        return $domain;
    }

    /**
     * @throws DomainNotFoundException
     */
    public function configureNotFoundRedirects(
        string $authority,
        NotFoundRedirects $notFoundRedirects,
        ?ApiKey $apiKey = null,
    ): Domain {
        $domain = $this->getPersistedDomain($authority, $apiKey);
        $domain->configureNotFoundRedirects($notFoundRedirects);

        $this->em->flush();

        return $domain;
    }

    /**
     * @throws DomainNotFoundException
     */
    private function getPersistedDomain(string $authority, ?ApiKey $apiKey): Domain
    {
        $domain = $this->findByAuthority($authority, $apiKey);
        if ($domain === null && $apiKey?->hasRole(Role::DOMAIN_SPECIFIC)) {
            // This API key is restricted to one domain and a different one was tried to be fetched
            throw DomainNotFoundException::fromAuthority($authority);
        }

        $domain = $domain ?? Domain::withAuthority($authority);
        $this->em->persist($domain);

        return $domain;
    }
}
