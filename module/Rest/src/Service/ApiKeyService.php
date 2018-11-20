<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use function sprintf;

class ApiKeyService implements ApiKeyServiceInterface
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function create(?Chronos $expirationDate = null): ApiKey
    {
        $key = new ApiKey($expirationDate);
        $this->em->persist($key);
        $this->em->flush();

        return $key;
    }

    public function check(string $key): bool
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $this->getByKey($key);
        return $apiKey !== null && $apiKey->isValid();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function disable(string $key): ApiKey
    {
        /** @var ApiKey|null $apiKey */
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

    public function getByKey(string $key): ?ApiKey
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $this->em->getRepository(ApiKey::class)->findOneBy([
            'key' => $key,
        ]);
        return $apiKey;
    }
}
