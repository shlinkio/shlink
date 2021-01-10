<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Shlinkio\Shlink\Rest\Entity\ApiKey;

final class ApiKeyCheckResult
{
    private ?ApiKey $apiKey;

    public function __construct(?ApiKey $apiKey = null)
    {
        $this->apiKey = $apiKey;
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
