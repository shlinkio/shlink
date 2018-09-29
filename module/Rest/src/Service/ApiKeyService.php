<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use function sprintf;

class ApiKeyService implements ApiKeyServiceInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Creates a new ApiKey with provided expiration date
     *
     * @param \DateTime $expirationDate
     * @return ApiKey
     */
    public function create(\DateTime $expirationDate = null): ApiKey
    {
        $key = new ApiKey();
        if ($expirationDate !== null) {
            $key->setExpirationDate($expirationDate);
        }

        $this->em->persist($key);
        $this->em->flush();

        return $key;
    }

    /**
     * Checks if provided key is a valid api key
     *
     * @param string $key
     * @return bool
     */
    public function check(string $key): bool
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $this->getByKey($key);
        return $apiKey !== null && $apiKey->isValid();
    }

    /**
     * Disables provided api key
     *
     * @param string $key
     * @return ApiKey
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
     * Lists all existing api keys
     *
     * @param bool $enabledOnly Tells if only enabled keys should be returned
     * @return ApiKey[]
     */
    public function listKeys(bool $enabledOnly = false): array
    {
        $conditions = $enabledOnly ? ['enabled' => true] : [];
        /** @var ApiKey[] $apiKeys */
        $apiKeys = $this->em->getRepository(ApiKey::class)->findBy($conditions);
        return $apiKeys;
    }

    /**
     * Tries to find one API key by its key string
     *
     * @param string $key
     * @return ApiKey|null
     */
    public function getByKey(string $key): ?ApiKey
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $this->em->getRepository(ApiKey::class)->findOneBy([
            'key' => $key,
        ]);
        return $apiKey;
    }
}
