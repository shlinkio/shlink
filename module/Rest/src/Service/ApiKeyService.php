<?php
namespace Shlinkio\Shlink\Rest\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

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
    public function create(\DateTime $expirationDate = null)
    {
        $key = new ApiKey();
        if (isset($expirationDate)) {
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
    public function check($key)
    {
        /** @var ApiKey $apiKey */
        $apiKey = $this->getByKey($key);
        if (! isset($apiKey)) {
            return false;
        }

        return $apiKey->isValid();
    }

    /**
     * Disables provided api key
     *
     * @param string $key
     * @return ApiKey
     */
    public function disable($key)
    {
        /** @var ApiKey $apiKey */
        $apiKey = $this->getByKey($key);
        if (! isset($apiKey)) {
            throw new InvalidArgumentException(sprintf('API key "%s" does not exist and can\'t be disabled', $key));
        }

        $apiKey->disable();
        $this->em->flush();
        return $apiKey;
    }

    /**
     * Lists all existing appi keys
     *
     * @param bool $enabledOnly Tells if only enabled keys should be returned
     * @return ApiKey[]
     */
    public function listKeys($enabledOnly = false)
    {
        $conditions = $enabledOnly ? ['enabled' => true] : [];
        return $this->em->getRepository(ApiKey::class)->findBy($conditions);
    }

    /**
     * Tries to find one API key by its key string
     *
     * @param string $key
     * @return ApiKey|null
     */
    public function getByKey($key)
    {
        return $this->em->getRepository(ApiKey::class)->findOneBy([
            'key' => $key,
        ]);
    }
}
