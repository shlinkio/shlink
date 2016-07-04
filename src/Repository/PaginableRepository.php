<?php
namespace Acelaya\UrlShortener\Repository;

interface PaginableRepository
{
    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $searchTerm
     * @param string|array|null $orderBy
     * @return array
     */
    public function findList($limit = null, $offset = null, $searchTerm = null, $orderBy = null);
}
