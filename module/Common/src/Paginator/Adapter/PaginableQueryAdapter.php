<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Paginator\Adapter;

use Doctrine\ORM\Query;
use Zend\Paginator\Adapter\AdapterInterface;

class PaginableQueryAdapter implements AdapterInterface
{
    /** @var Query */
    private $query;
    /** @var int */
    private $totalItems;

    public function __construct(Query $query, int $totalItems)
    {
        $this->query = $query;
        $this->totalItems = $totalItems;
    }

    public function getItems($offset, $itemCountPerPage): iterable
    {
        return $this->query
            ->setMaxResults($itemCountPerPage)
            ->setFirstResult($offset)
            ->iterate();
    }

    public function count(): int
    {
        return $this->totalItems;
    }
}
