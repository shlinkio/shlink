<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function sprintf;

class ApiKeyService implements ApiKeyServiceInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function create(
        ?Chronos $expirationDate = null,
        ?string $name = null,
        RoleDefinition ...$roleDefinitions,
    ): ApiKey {
        $key = $this->buildApiKeyWithParams($expirationDate, $name);
        foreach ($roleDefinitions as $definition) {
            $key->registerRole($definition);
        }

        $this->em->persist($key);
        $this->em->flush();

        return $key;
    }

    private function buildApiKeyWithParams(?Chronos $expirationDate, ?string $name): ApiKey
    {
        return match (true) {
            $expirationDate !== null && $name !== null => ApiKey::fromMeta(
                ApiKeyMeta::withNameAndExpirationDate($name, $expirationDate),
            ),
            $expirationDate !== null => ApiKey::fromMeta(ApiKeyMeta::withExpirationDate($expirationDate)),
            $name !== null => ApiKey::fromMeta(ApiKeyMeta::withName($name)),
            default => ApiKey::create(),
        };
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

    private function getByKey(string $key): ?ApiKey
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $this->em->getRepository(ApiKey::class)->findOneBy([
            'key' => $key,
        ]);
        return $apiKey;
    }
}
