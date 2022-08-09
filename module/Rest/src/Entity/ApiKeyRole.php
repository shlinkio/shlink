<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Rest\ApiKey\Role;

class ApiKeyRole extends AbstractEntity
{
    public function __construct(private Role $roleName, private array $meta, private ApiKey $apiKey)
    {
    }

    public function role(): Role
    {
        return $this->roleName;
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function updateMeta(array $newMeta): void
    {
        $this->meta = $newMeta;
    }

    public function apiKey(): ApiKey
    {
        return $this->apiKey;
    }
}
