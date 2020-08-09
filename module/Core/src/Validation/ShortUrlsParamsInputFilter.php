<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Validation;

use Laminas\Filter;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator;
use Shlinkio\Shlink\Common\Validation;

use function is_numeric;

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

        $this->add($this->createNumericInput(self::PAGE, 1));

        $tags = $this->createArrayInput(self::TAGS, false);
        $tags->getFilterChain()->attach(new Filter\StringToLower())
                               ->attach(new Filter\PregReplace(['pattern' => '/ /', 'replacement' => '-']));
        $this->add($tags);

        $this->add($this->createNumericInput(self::ITEMS_PER_PAGE, -1));
    }

    private function createNumericInput(string $name, int $min): Input
    {
        $input = $this->createInput($name, false);
        $input->getValidatorChain()->attach(new Validator\Callback(fn ($value) => is_numeric($value)))
                                   ->attach(new Validator\GreaterThan(['min' => $min, 'inclusive' => true]));

        return $input;
    }
}
