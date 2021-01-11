<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Exception\DomainNotFoundException;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function Functional\map;

class DomainService implements DomainServiceInterface
{
    private EntityManagerInterface $em;
    private string $defaultDomain;

    public function __construct(EntityManagerInterface $em, string $defaultDomain)
    {
        $this->em = $em;
        $this->defaultDomain = $defaultDomain;
    }

    /**
     * @return DomainItem[]
     */
    public function listDomains(?ApiKey $apiKey = null): array
    {
        /** @var DomainRepositoryInterface $repo */
        $repo = $this->em->getRepository(Domain::class);
        $domains = $repo->findDomainsWithout($this->defaultDomain, $apiKey);
        $mappedDomains = map($domains, fn (Domain $domain) => new DomainItem($domain->getAuthority(), false));

        if ($apiKey !== null && $apiKey->hasRole(Role::DOMAIN_SPECIFIC)) {
            return $mappedDomains;
        }

        return [
            new DomainItem($this->defaultDomain, true),
            ...$mappedDomains,
        ];
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

    public function getOrCreate(string $authority): Domain
    {
        $repo = $this->em->getRepository(Domain::class);
        /** @var Domain|null $domain */
        $domain = $repo->findOneBy(['authority' => $authority]) ?? new Domain($authority);

        $this->em->persist($domain);
        $this->em->flush();

        return $domain;
    }
}
