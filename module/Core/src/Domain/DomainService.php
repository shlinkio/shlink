<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Config\EmptyNotFoundRedirectConfig;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function array_map;

readonly class DomainService implements DomainServiceInterface
{
    public function __construct(private EntityManagerInterface $em, private UrlShortenerOptions $urlShortenerOptions)
    {
    }

    /**
     * @return DomainItem[]
     */
    public function listDomains(ApiKey|null $apiKey = null): array
    {
        [$default, $domains] = $this->defaultDomainAndRest($apiKey);
        $mappedDomains = array_map(fn (Domain $domain) => DomainItem::forNonDefaultDomain($domain), $domains);

        if ($apiKey?->hasRole(Role::DOMAIN_SPECIFIC)) {
            return $mappedDomains;
        }

        return [
            DomainItem::forDefaultDomain(
                $this->urlShortenerOptions->defaultDomain,
                $default ?? new EmptyNotFoundRedirectConfig(),
            ),
            ...$mappedDomains,
        ];
    }

    /**
     * @return array{Domain|null, Domain[]}
     */
    private function defaultDomainAndRest(ApiKey|null $apiKey): array
    {
        /** @var DomainRepositoryInterface $repo */
        $repo = $this->em->getRepository(Domain::class);
        $allDomains = $repo->findDomains($apiKey);
        $defaultDomain = null;
        $restOfDomains = [];

        foreach ($allDomains as $domain) {
            if ($domain->authority === $this->urlShortenerOptions->defaultDomain) {
                $defaultDomain = $domain;
            } else {
                $restOfDomains[] = $domain;
            }
        }

        return [$defaultDomain, $restOfDomains];
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

    public function findByAuthority(string $authority, ApiKey|null $apiKey = null): Domain|null
    {
        return $this->em->getRepository(Domain::class)->findOneByAuthority($authority, $apiKey);
    }

    /**
     * @throws DomainNotFoundException
     */
    public function getOrCreate(string $authority, ApiKey|null $apiKey = null): Domain
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
        ApiKey|null $apiKey = null,
    ): Domain {
        $domain = $this->getPersistedDomain($authority, $apiKey);
        $domain->configureNotFoundRedirects($notFoundRedirects);

        $this->em->flush();

        return $domain;
    }

    /**
     * @throws DomainNotFoundException
     */
    private function getPersistedDomain(string $authority, ApiKey|null $apiKey): Domain
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
