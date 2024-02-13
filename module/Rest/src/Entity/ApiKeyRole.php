<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Rest\ApiKey\Role;

class ApiKeyRole extends AbstractEntity
{
    public function __construct(public readonly Role $role, private array $meta, public readonly ApiKey $apiKey)
    {
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function updateMeta(array $newMeta): void
    {
        $this->meta = $newMeta;
    }
}
