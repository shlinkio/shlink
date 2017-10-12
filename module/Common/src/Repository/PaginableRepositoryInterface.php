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
    public function findList($limit = null, $offset = null, $searchTerm = null, array $tags = [], $orderBy = null);

    /**
     * Counts the number of elements in a list using provided filtering data
     *
     * @param null $searchTerm
     * @param array $tags
     * @return int
     */
    public function countList($searchTerm = null, array $tags = []);
}
