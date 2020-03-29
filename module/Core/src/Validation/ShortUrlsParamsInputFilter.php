<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Validation;

use Laminas\Filter;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator;
use Shlinkio\Shlink\Common\Validation;

class ShortUrlsParamsInputFilter extends InputFilter
{
    use Validation\InputFactoryTrait;

    public const PAGE = 'page';
    public const SEARCH_TERM = 'searchTerm';
    public const TAGS = 'tags';
    public const START_DATE = 'startDate';
    public const END_DATE = 'endDate';

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

        $page = $this->createInput(self::PAGE, false);
        $page->getValidatorChain()->attach(new Validator\Digits())
                                  ->attach(new Validator\GreaterThan(['min' => 1, 'inclusive' => true]));
        $this->add($page);

        $tags = $this->createArrayInput(self::TAGS, false);
        $tags->getFilterChain()->attach(new Filter\StringToLower())
                               ->attach(new Filter\PregReplace(['pattern' => '/ /', 'replacement' => '-']));
        $this->add($tags);
    }
}
