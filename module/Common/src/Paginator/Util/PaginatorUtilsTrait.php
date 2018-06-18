<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Paginator\Util;

use Zend\Paginator\Paginator;
use Zend\Stdlib\ArrayUtils;

trait PaginatorUtilsTrait
{
    protected function serializePaginator(Paginator $paginator): array
    {
        return [
            'data' => ArrayUtils::iteratorToArray($paginator->getCurrentItems()),
            'pagination' => [
                'currentPage' => $paginator->getCurrentPageNumber(),
                'pagesCount' => $paginator->count(),
                'itemsPerPage' => $paginator->getItemCountPerPage(),
                'itemsInCurrentPage' => $paginator->getCurrentItemCount(),
                'totalItems' => $paginator->getTotalItemCount(),
            ],
        ];
    }

    /**
     * Checks if provided paginator is in last page
     *
     * @param Paginator $paginator
     * @return bool
     */
    protected function isLastPage(Paginator $paginator): bool
    {
        return $paginator->getCurrentPageNumber() >= $paginator->count();
    }
}
