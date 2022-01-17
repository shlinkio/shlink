<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlsCountFiltering
{
    public function __construct(
        private ?string $searchTerm = null,
        private array $tags = [],
        private ?string $tagsMode = null,
        private ?DateRange $dateRange = null,
        private ?ApiKey $apiKey = null,
    ) {
    }

    public static function fromParams(ShortUrlsParams $params, ?ApiKey $apiKey): self
    {
        return new self($params->searchTerm(), $params->tags(), $params->tagsMode(), $params->dateRange(), $apiKey);
    }

    public function searchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function tagsMode(): ?string
    {
        return $this->tagsMode;
    }

    public function dateRange(): ?DateRange
    {
        return $this->dateRange;
    }

    public function apiKey(): ?ApiKey
    {
        return $this->apiKey;
    }
}
