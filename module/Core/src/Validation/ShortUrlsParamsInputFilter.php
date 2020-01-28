<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Validation;

use DateTime;
use Laminas\Filter;
use Laminas\InputFilter\ArrayInput;
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
        $startDate = $this->createInput(self::START_DATE, false);
        $startDate->getValidatorChain()->attach(new Validator\Date(['format' => DateTime::ATOM]));
        $this->add($startDate);

        $endDate = $this->createInput(self::END_DATE, false);
        $endDate->getValidatorChain()->attach(new Validator\Date(['format' => DateTime::ATOM]));
        $this->add($endDate);

        $this->add($this->createInput(self::SEARCH_TERM, false));

        $page = $this->createInput(self::PAGE, false);
        $page->getValidatorChain()->attach(new Validator\Digits())
                                  ->attach(new Validator\GreaterThan(['min' => 1, 'inclusive' => true]));
        $this->add($page);

        $tags = new ArrayInput(self::TAGS);
        $tags->setRequired(false)
             ->getFilterChain()->attach(new Filter\StripTags())
                               ->attach(new Filter\StringTrim())
                               ->attach(new Filter\StringToLower())
                               ->attach(new Validation\SluggerFilter());
        $this->add($tags);
    }
}
