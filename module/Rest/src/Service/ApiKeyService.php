<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Repository\ApiKeyRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

readonly class ApiKeyService implements ApiKeyServiceInterface
{
    public function __construct(private EntityManagerInterface $em, private ApiKeyRepositoryInterface $repo)
    {
    }

    public function create(ApiKeyMeta $apiKeyMeta): ApiKey
    {
        $apiKey = ApiKey::fromMeta($apiKeyMeta);

        $this->em->persist($apiKey);
        $this->em->flush();

        return $apiKey;
    }

    public function createInitial(string $key): ApiKey|null
    {
        return $this->repo->createInitialApiKey($key);
    }

    public function check(string $key): ApiKeyCheckResult
    {
        $apiKey = $this->findByKey($key);
        return new ApiKeyCheckResult($apiKey);
    }

    /**
     * @inheritDoc
     */
    public function disableByName(string $apiKeyName): ApiKey
    {
        return $this->disableApiKey($this->repo->findOneBy(['name' => $apiKeyName]));
    }

    /**
     * @inheritDoc
     */
    public function disableByKey(string $key): ApiKey
    {
        return $this->disableApiKey($this->findByKey($key));
    }

    private function disableApiKey(ApiKey|null $apiKey): ApiKey
    {
        if ($apiKey === null) {
            throw new InvalidArgumentException('Provided API key does not exist and can\'t be disabled');
        }

        $apiKey->disable();
        $this->em->flush();

        return $apiKey;
    }

    /**
     * @return ApiKey[]
     */
    public function listKeys(bool $enabledOnly = false): array
    {
        $conditions = $enabledOnly ? ['enabled' => true] : [];
        return $this->repo->findBy($conditions);
    }

    private function findByKey(string $key): ApiKey|null
    {
        return $this->repo->findOneBy(['key' => ApiKey::hashKey($key)]);
    }

    /**
     * @inheritDoc
     */
    public function existsWithName(string $apiKeyName): bool
    {
        return $this->repo->count(['name' => $apiKeyName]) > 0;
    }
}
