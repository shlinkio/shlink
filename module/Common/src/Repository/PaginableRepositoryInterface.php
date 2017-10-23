<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Repository;

interface PaginableRepositoryInterface
{
    /**
     * Gets a list of elements using provided filtering data
     *
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $searchTerm
     * @param array $tags
     * @param string|array|null $orderBy
     * @return array
     */
    public function findList(
        int $limit = null,
        int $offset = null,
        string $searchTerm = null,
        array $tags = [],
        $orderBy = null
    ): array;

    /**
     * Counts the number of elements in a list using provided filtering data
     *
     * @param string|null $searchTerm
     * @param array $tags
     * @return int
     */
    public function countList(string $searchTerm = null, array $tags = []): int;
}
