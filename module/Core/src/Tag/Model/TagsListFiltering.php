<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use Shlinkio\Shlink\Rest\Entity\ApiKey;

final class TagsListFiltering
{
    public function __construct(
        private ?int $limit = null,
        private ?int $offset = null,
        private ?string $searchTerm = null,
        private ?ApiKey $apiKey = null,
    ) {
    }

    public function limit(): ?int
    {
        return $this->limit;
    }

    public function offset(): ?int
    {
        return $this->offset;
    }

    public function searchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function apiKey(): ?ApiKey
    {
        return $this->apiKey;
    }
}
