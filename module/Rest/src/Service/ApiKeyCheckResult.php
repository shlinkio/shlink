<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Shlinkio\Shlink\Rest\Entity\ApiKey;

final class ApiKeyCheckResult
{
    public function __construct(private ?ApiKey $apiKey = null)
    {
    }

    public function isValid(): bool
    {
        return $this->apiKey !== null && $this->apiKey->isValid();
    }

    public function apiKey(): ?ApiKey
    {
        return $this->apiKey;
    }
}
