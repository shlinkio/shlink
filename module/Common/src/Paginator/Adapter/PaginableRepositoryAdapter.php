<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Paginator\Adapter;

use Shlinkio\Shlink\Common\Repository\PaginableRepositoryInterface;
use Zend\Paginator\Adapter\AdapterInterface;

use function strip_tags;
use function trim;

class PaginableRepositoryAdapter implements AdapterInterface
{
    public const ITEMS_PER_PAGE = 10;

    /** @var PaginableRepositoryInterface */
    private $paginableRepository;
    /** @var null|string */
    private $searchTerm;
    /** @var null|array|string */
    private $orderBy;
    /** @var array */
    private $tags;

    public function __construct(
        PaginableRepositoryInterface $paginableRepository,
        $searchTerm = null,
        array $tags = [],
        $orderBy = null
    ) {
        $this->paginableRepository = $paginableRepository;
        $this->searchTerm = $searchTerm !== null ? trim(strip_tags($searchTerm)) : null;
        $this->orderBy = $orderBy;
        $this->tags = $tags;
    }

    /**
     * Returns a collection of items for a page.
     *
     * @param  int $offset Page offset
     * @param  int $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage): array
    {
        return $this->paginableRepository->findList(
            $itemCountPerPage,
            $offset,
            $this->searchTerm,
            $this->tags,
            $this->orderBy
        );
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count(): int
    {
        return $this->paginableRepository->countList($this->searchTerm, $this->tags);
    }
}
