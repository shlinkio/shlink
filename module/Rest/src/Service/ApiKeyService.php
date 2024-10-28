<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Repository\ApiKeyRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function sprintf;

class ApiKeyService implements ApiKeyServiceInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
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
     * @throws InvalidArgumentException
     */
    public function disable(string $key): ApiKey
    {
        $apiKey = $this->getByKey($key);
        if ($apiKey === null) {
            throw new InvalidArgumentException(sprintf('API key "%s" does not exist and can\'t be disabled', $key));
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
        /** @var ApiKey[] $apiKeys */
        $apiKeys = $this->em->getRepository(ApiKey::class)->findBy($conditions);
        return $apiKeys;
    }

    private function getByKey(string $key): ApiKey|null
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $this->em->getRepository(ApiKey::class)->findOneBy([
            'key' => $key,
        ]);
        return $apiKey;
    }
}
