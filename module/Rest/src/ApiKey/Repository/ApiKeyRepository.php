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
     * Will create provided API key with admin permissions, only if there's no other API keys yet
     */
    public function createInitialApiKey(string $apiKey): ApiKey|null
    {
        $em = $this->getEntityManager();
        return $em->wrapInTransaction(function () use ($apiKey, $em): ApiKey|null {
            // Ideally this would be a SELECT COUNT(...), but MsSQL and Postgres do not allow locking on aggregates
            // Because of that we check if at least one result exists
            $firstResult = $em->createQueryBuilder()->select('a.id')
                                                    ->from(ApiKey::class, 'a')
                                                    ->setMaxResults(1)
                                                    ->getQuery()
                                                    ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                                                    ->getOneOrNullResult();

            // Do not create an initial API key if other keys already exist
            if ($firstResult !== null) {
                return null;
            }

            $new = ApiKey::fromMeta(ApiKeyMeta::fromParams(key: $apiKey));
            $em->persist($new);
            $em->flush();

            return $new;
        });
    }
}
