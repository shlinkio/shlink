<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;

class ApiKeyRole extends AbstractEntity
{
    public function __construct(private string $roleName, private array $meta, private ApiKey $apiKey)
    {
    }

    public function name(): string
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
