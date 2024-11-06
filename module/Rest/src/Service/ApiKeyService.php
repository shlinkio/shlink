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
    public function __construct(private EntityManagerInterface $em)
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
        /** @var ApiKeyRepositoryInterface $repo */
        $repo = $this->em->getRepository(ApiKey::class);
        return $repo->createInitialApiKey($key);
    }

    public function check(string $key): ApiKeyCheckResult
    {
        $apiKey = $this->getByKey($key);
        return new ApiKeyCheckResult($apiKey);
    }

    /**
     * @inheritDoc
     */
    public function disableByName(string $apiKeyName): ApiKey
    {
        return $this->disableApiKey($this->em->getRepository(ApiKey::class)->findOneBy([
            'name' => $apiKeyName,
        ]));
    }

    /**
     * @inheritDoc
     */
    public function disableByKey(string $key): ApiKey
    {
        return $this->disableApiKey($this->getByKey($key));
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
        return $this->em->getRepository(ApiKey::class)->findBy($conditions);
    }

    private function getByKey(string $key): ApiKey|null
    {
        return $this->em->getRepository(ApiKey::class)->findOneBy([
            'key' => ApiKey::hashKey($key),
        ]);
    }
}
