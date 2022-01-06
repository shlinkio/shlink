<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use Shlinkio\Shlink\Core\Model\AbstractInfinitePaginableListParams;

final class TagsParams extends AbstractInfinitePaginableListParams
{
    private function __construct(private ?string $searchTerm, ?int $page, ?int $itemsPerPage)
    {
        parent::__construct($page, $itemsPerPage);
    }

    public static function fromRawData(array $query): self
    {
        return new self(
            $query['searchTerm'] ?? null,
            isset($query['page']) ? (int) $query['page'] : null,
            isset($query['itemsPerPage']) ? (int) $query['itemsPerPage'] : null,
        );
    }

    public function searchTerm(): ?string
    {
        return $this->searchTerm;
    }
}
