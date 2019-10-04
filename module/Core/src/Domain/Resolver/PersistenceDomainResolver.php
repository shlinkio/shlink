<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Domain;

class PersistenceDomainResolver implements DomainResolverInterface
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function resolveDomain(?string $domain): ?Domain
    {
        if ($domain === null) {
            return null;
        }

        /** @var Domain|null $existingDomain */
        $existingDomain = $this->em->getRepository(Domain::class)->findOneBy(['authority' => $domain]);
        return $existingDomain ?? new Domain($domain);
    }
}
