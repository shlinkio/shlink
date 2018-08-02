<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ApiKeyServiceInterface
{
    /**
     * Creates a new ApiKey with provided expiration date
     *
     * @param \DateTime $expirationDate
     * @return ApiKey
     */
    public function create(\DateTime $expirationDate = null): ApiKey;

    /**
     * Checks if provided key is a valid api key
     *
     * @param string $key
     * @return bool
     */
    public function check(string $key): bool;

    /**
     * Disables provided api key
     *
     * @param string $key
     * @return ApiKey
     * @throws InvalidArgumentException
     */
    public function disable(string $key): ApiKey;

    /**
     * Lists all existing api keys
     *
     * @param bool $enabledOnly Tells if only enabled keys should be returned
     * @return ApiKey[]
     */
    public function listKeys(bool $enabledOnly = false): array;

    /**
     * Tries to find one API key by its key string
     *
     * @param string $key
     * @return ApiKey|null
     */
    public function getByKey(string $key): ?ApiKey;
}
