<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\ApiKey\Repository;

use Doctrine\DBAL\LockMode;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

/**
 * @extends EntitySpecificationRepository<ApiKey>
 */
class ApiKeyRepository extends EntitySpecificationRepository implements ApiKeyRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function createInitialApiKey(string $apiKey): ApiKey|null
    {
        $em = $this->getEntityManager();
        return $em->wrapInTransaction(function () use ($apiKey, $em): ApiKey|null {
            $firstResult = $em->createQueryBuilder()
                ->select('a.id')
                ->from(ApiKey::class, 'a')
                ->setMaxResults(1)
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            // Do not create an initial API key if other keys already exist
            if ($firstResult !== null) {
                return null;
            }

            $initialApiKey = ApiKey::fromMeta(ApiKeyMeta::fromParams(key: $apiKey));
            $em->persist($initialApiKey);

            return $initialApiKey;
        });
    }

    /**
     * @inheritDoc
     */
    public function nameExists(string $name): bool
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('a.id')
           ->from(ApiKey::class, 'a')
           ->where($qb->expr()->eq('a.name', ':name'))
           ->setParameter('name', $name)
           ->setMaxResults(1);

        // Lock for update, to avoid a race condition that inserts a duplicate name after we have checked if one existed
        $query = $qb->getQuery();
        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

        return $query->getOneOrNullResult() !== null;
    }
}
