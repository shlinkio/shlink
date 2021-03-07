<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ApiKeyServiceInterface
{
    public function create(
        ?Chronos $expirationDate = null,
        ?string $name = null,
        RoleDefinition ...$roleDefinitions
    ): ApiKey;

    public function check(string $key): ApiKeyCheckResult;

    /**
     * @throws InvalidArgumentException
     */
    public function disable(string $key): ApiKey;

    /**
     * @return ApiKey[]
     */
    public function listKeys(bool $enabledOnly = false): array;
}
