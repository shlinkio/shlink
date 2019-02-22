<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Paginator;

use IteratorAggregate;
use Zend\Paginator\Paginator;

class ImplicitLoopPaginator implements IteratorAggregate
{
    /** @var Paginator */
    private $paginator;
    /** @var callable */
    private $valueParser;

    public function __construct(Paginator $paginator, callable $valueParser = null)
    {
        $this->paginator = $paginator;
        $this->valueParser = $valueParser ?? function ($value) {
            return $value;
        };
    }

    public function getIterator(): iterable
    {
        $totalPages = $this->paginator->count();
        $processedPages = 0;

        do {
            $processedPages++;
            $this->paginator->setCurrentPageNumber($processedPages);

            foreach ($this->paginator as $key => $value) {
                yield $key => ($this->valueParser)($value);
            }
        } while ($processedPages < $totalPages);
    }
}
