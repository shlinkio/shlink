<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Service;

use Shlinkio\Shlink\Rest\Entity\ApiKey;

final readonly class ApiKeyCheckResult
{
    public function __construct(public ApiKey|null $apiKey = null)
    {
    }

    public function isValid(): bool
    {
        return $this->apiKey !== null && $this->apiKey->isValid();
    }
}
