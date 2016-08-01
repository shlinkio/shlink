<?php
namespace Shlinkio\Shlink\Common\Paginator\Util;

use Zend\Paginator\Paginator;
use Zend\Stdlib\ArrayUtils;

trait PaginatorUtilsTrait
{
    protected function serializePaginator(Paginator $paginator)
    {
        return [
            'data' => ArrayUtils::iteratorToArray($paginator->getCurrentItems()),
            'pagination' => [
                'currentPage' => $paginator->getCurrentPageNumber(),
                'pagesCount' => $paginator->count(),
            ],
        ];
    }

    /**
     * Checks if provided paginator is in last page
     *
     * @param Paginator $paginator
     * @return bool
     */
    protected function isLastPage(Paginator $paginator)
    {
        return $paginator->getCurrentPageNumber() >= $paginator->count();
    }
}
