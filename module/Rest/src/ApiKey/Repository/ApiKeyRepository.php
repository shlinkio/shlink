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
            // Ideally this would be a SELECT COUNT(...), but MsSQL and Postgres do not allow locking on aggregates
            // Because of that we check if at least one result exists
            $firstResult = $em->createQueryBuilder()->select('a.id')
                                                    ->from(ApiKey::class, 'a')
                                                    ->setMaxResults(1)
                                                    ->getQuery()
                                                    ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                                                    ->getOneOrNullResult();

            if ($firstResult === null) {
                $em->persist(ApiKey::fromKey($apiKey));
                $em->flush();
            }
        });
    }
}
