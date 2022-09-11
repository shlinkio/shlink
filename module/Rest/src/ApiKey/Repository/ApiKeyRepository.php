<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Repository;

use Doctrine\DBAL\LockMode;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ApiKeyRepository extends EntitySpecificationRepository implements ApiKeyRepositoryInterface
{
    public function createInitialApiKey(string $apiKey): void
    {
        $this->getEntityManager()->wrapInTransaction(function () use ($apiKey): void {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $amountOfApiKeys = $qb->select('COUNT(a.id)')
                                  ->from(ApiKey::class, 'a')
                                  ->getQuery()
                                  ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                                  ->getSingleScalarResult();

            if ($amountOfApiKeys === 0) {
                $this->getEntityManager()->persist(ApiKey::fromKey($apiKey));
                $this->getEntityManager()->flush();
            }
        });
    }
}
