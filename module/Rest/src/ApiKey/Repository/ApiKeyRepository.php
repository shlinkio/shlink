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
        $em = $this->getEntityManager();
        $em->wrapInTransaction(function () use ($apiKey, $em): void {
            $amountOfApiKeys = $em->createQueryBuilder()->select('COUNT(a.id)')
                                                        ->from(ApiKey::class, 'a')
                                                        ->getQuery()
                                                        ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                                                        ->getSingleScalarResult();

            if ($amountOfApiKeys === 0) {
                $em->persist(ApiKey::fromKey($apiKey));
                $em->flush();
            }
        });
    }
}
