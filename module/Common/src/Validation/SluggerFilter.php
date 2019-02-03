<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Validation;

use Cocur\Slugify;
use Zend\Filter\Exception;
use Zend\Filter\FilterInterface;

class SluggerFilter implements FilterInterface
{
    /** @var Slugify\SlugifyInterface */
    private $slugger;

    public function __construct(?Slugify\SlugifyInterface $slugger = null)
    {
        $this->slugger = $slugger ?: new Slugify\Slugify(['lowercase' => false]);
    }

    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws Exception\RuntimeException If filtering $value is impossible
     * @return mixed
     */
    public function filter($value)
    {
        return ! empty($value) ? $this->slugger->slugify($value) : null;
    }
}
