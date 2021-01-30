<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Validation;

use Laminas\InputFilter\InputFilter;
use Shlinkio\Shlink\Common\Validation;

class ShortUrlsParamsInputFilter extends InputFilter
{
    use Validation\InputFactoryTrait;

    public const PAGE = 'page';
    public const SEARCH_TERM = 'searchTerm';
    public const TAGS = 'tags';
    public const START_DATE = 'startDate';
    public const END_DATE = 'endDate';
    public const ITEMS_PER_PAGE = 'itemsPerPage';

    public function __construct(array $data)
    {
        $this->initialize();
        $this->setData($data);
    }

    private function initialize(): void
    {
        $this->add($this->createDateInput(self::START_DATE, false));
        $this->add($this->createDateInput(self::END_DATE, false));

        $this->add($this->createInput(self::SEARCH_TERM, false));

        $this->add($this->createNumericInput(self::PAGE, false));
        $this->add($this->createNumericInput(self::ITEMS_PER_PAGE, false, -1));

        $this->add($this->createTagsInput(self::TAGS, false));
    }
}
