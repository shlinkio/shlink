<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ApiKeyServiceInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function create(ApiKeyMeta $apiKeyMeta): ApiKey;

    public function createInitial(string $key): ApiKey|null;

    public function check(string $key): ApiKeyCheckResult;

    /**
     * @throws InvalidArgumentException
     */
    public function disableByName(string $apiKeyName): ApiKey;

    /**
     * @deprecated Use `self::disableByName($name)` instead
     * @throws InvalidArgumentException
     */
    public function disableByKey(string $key): ApiKey;

    /**
     * @return ApiKey[]
     */
    public function listKeys(bool $enabledOnly = false): array;

    /**
     * @throws InvalidArgumentException If an API key with oldName does not exist, or newName is in use by another one
     */
    public function renameApiKey(Renaming $apiKeyRenaming): ApiKey;
}
