<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface ApiKeyServiceInterface
{
    public function create(?Chronos $expirationDate = null): ApiKey;

    public function check(string $key): bool;

    /**
     * @throws InvalidArgumentException
     */
    public function disable(string $key): ApiKey;

    public function listKeys(bool $enabledOnly = false): array;

    public function getByKey(string $key): ?ApiKey;
}
