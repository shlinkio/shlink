<?php
namespace Acelaya\UrlShortener\Paginator\Util;

use Zend\Paginator\Paginator;
use Zend\Stdlib\ArrayUtils;

trait PaginatorSerializerTrait
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
}
