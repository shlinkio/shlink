<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Repository\ApiKeyRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function sprintf;

readonly class ApiKeyService implements ApiKeyServiceInterface
{
    public function __construct(private EntityManagerInterface $em, private ApiKeyRepositoryInterface $repo)
    {
    }

    public function create(ApiKeyMeta $apiKeyMeta): ApiKey
    {
        return $this->em->wrapInTransaction(function () use ($apiKeyMeta) {
            $apiKey = ApiKey::fromMeta($apiKeyMeta);
            // TODO If name is auto-generated, do not throw. Instead, re-generate a new key
            if ($this->repo->nameExists($apiKey->name)) {
                throw new InvalidArgumentException(
                    sprintf('Another API key with name "%s" already exists', $apiKeyMeta->name),
                );
            }

            $this->em->persist($apiKey);
            $this->em->flush();

            return $apiKey;
        });
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

    /**
     * @inheritDoc
     */
    public function renameApiKey(Renaming $apiKeyRenaming): ApiKey
    {
        $apiKey = $this->repo->findOneBy(['name' => $apiKeyRenaming->oldName]);
        if ($apiKey === null) {
            throw new InvalidArgumentException(
                sprintf('API key with name "%s" could not be found', $apiKeyRenaming->oldName),
            );
        }

        if (! $apiKeyRenaming->nameChanged()) {
            return $apiKey;
        }

        return $this->em->wrapInTransaction(function () use ($apiKeyRenaming, $apiKey) {
            if ($this->repo->nameExists($apiKeyRenaming->newName)) {
                throw new InvalidArgumentException(
                    sprintf('Another API key with name "%s" already exists', $apiKeyRenaming->newName),
                );
            }

            $apiKey->name = $apiKeyRenaming->newName;
            $this->em->flush();

            return $apiKey;
        });
    }

    private function findByKey(string $key): ApiKey|null
    {
        return $this->repo->findOneBy(['key' => ApiKey::hashKey($key)]);
    }
}
