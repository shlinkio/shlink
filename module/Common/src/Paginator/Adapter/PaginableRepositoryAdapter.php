<?php
namespace Shlinkio\Shlink\Common\Paginator\Adapter;

use Shlinkio\Shlink\Common\Repository\PaginableRepositoryInterface;
use Zend\Paginator\Adapter\AdapterInterface;

class PaginableRepositoryAdapter implements AdapterInterface
{
    const ITEMS_PER_PAGE = 10;

    /**
     * @var PaginableRepositoryInterface
     */
    private $paginableRepository;
    /**
     * @var null|string
     */
    private $searchTerm;
    /**
     * @var null|array|string
     */
    private $orderBy;

    public function __construct(PaginableRepositoryInterface $paginableRepository, $searchQuery = null, $orderBy = null)
    {
        $this->paginableRepository = $paginableRepository;
        $this->searchTerm = trim(strip_tags($searchQuery));
        $this->orderBy = $orderBy;
    }

    /**
     * Returns a collection of items for a page.
     *
     * @param  int $offset Page offset
     * @param  int $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        return $this->paginableRepository->findList($itemCountPerPage, $offset, $this->searchTerm, $this->orderBy);
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
    public function count()
    {
        return $this->paginableRepository->countList($this->searchTerm);
    }
}
