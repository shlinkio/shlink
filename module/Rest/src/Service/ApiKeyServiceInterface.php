<?php
namespace Shlinkio\Shlink\Rest\Service;

use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ApiKeyServiceInterface
{
    /**
     * Creates a new ApiKey with provided expiration date
     *
     * @param \DateTime $expirationDate
     * @return ApiKey
     */
    public function create(\DateTime $expirationDate = null);

    /**
     * Checks if provided key is a valid api key
     *
     * @param string $key
     * @return bool
     */
    public function check($key);

    /**
     * Disables provided api key
     *
     * @param string $key
     * @return ApiKey
     */
    public function disable($key);

    /**
     * Lists all existing appi keys
     *
     * @param bool $enabledOnly Tells if only enabled keys should be returned
     * @return ApiKey[]
     */
    public function listKeys($enabledOnly = false);
}
