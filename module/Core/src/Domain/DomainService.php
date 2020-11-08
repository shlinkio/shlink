<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Domain\Repository\DomainRepositoryInterface;
use Shlinkio\Shlink\Core\Entity\Domain;

class DomainService implements DomainServiceInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return Domain[]
     */
    public function listDomainsWithout(?string $excludeDomain = null): array
    {
        /** @var DomainRepositoryInterface $repo */
        $repo = $this->em->getRepository(Domain::class);
        return $repo->findDomainsWithout($excludeDomain);
    }
}
