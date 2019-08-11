<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Paginator\Util;

use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Zend\Paginator\Paginator;
use Zend\Stdlib\ArrayUtils;

use function array_map;
use function sprintf;

trait PaginatorUtilsTrait
{
    private function serializePaginator(Paginator $paginator, ?DataTransformerInterface $transformer = null): array
    {
        return [
            'data' => $this->serializeItems(ArrayUtils::iteratorToArray($paginator->getCurrentItems()), $transformer),
            'pagination' => [
                'currentPage' => $paginator->getCurrentPageNumber(),
                'pagesCount' => $paginator->count(),
                'itemsPerPage' => $paginator->getItemCountPerPage(),
                'itemsInCurrentPage' => $paginator->getCurrentItemCount(),
                'totalItems' => $paginator->getTotalItemCount(),
            ],
        ];
    }

    private function serializeItems(array $items, ?DataTransformerInterface $transformer = null): array
    {
        return $transformer === null ? $items : array_map([$transformer, 'transform'], $items);
    }

    private function isLastPage(Paginator $paginator): bool
    {
        return $paginator->getCurrentPageNumber() >= $paginator->count();
    }

    private function formatCurrentPageMessage(Paginator $paginator, string $pattern): string
    {
        return sprintf($pattern, $paginator->getCurrentPageNumber(), $paginator->count());
    }
}
