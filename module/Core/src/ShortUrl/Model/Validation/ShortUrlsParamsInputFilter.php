<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model\Validation;

use Laminas\InputFilter\InputFilter;
use Laminas\Validator\InArray;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Common\Validation\InputFactory;
use Shlinkio\Shlink\Core\ShortUrl\Model\OrderableField;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;

use function Shlinkio\Shlink\Core\enumValues;

/** @extends InputFilter<mixed> */
class ShortUrlsParamsInputFilter extends InputFilter
{
    public const string PAGE = 'page';
    public const string SEARCH_TERM = 'searchTerm';
    public const string TAGS = 'tags';
    public const string START_DATE = 'startDate';
    public const string END_DATE = 'endDate';
    public const string ITEMS_PER_PAGE = 'itemsPerPage';
    public const string TAGS_MODE = 'tagsMode';
    public const string ORDER_BY = 'orderBy';
    public const string EXCLUDE_MAX_VISITS_REACHED = 'excludeMaxVisitsReached';
    public const string EXCLUDE_PAST_VALID_UNTIL = 'excludePastValidUntil';
    public const string DOMAIN = 'domain';

    public function __construct(array $data)
    {
        $this->initialize();
        $this->setData($data);
    }

    private function initialize(): void
    {
        $this->add(InputFactory::date(self::START_DATE));
        $this->add(InputFactory::date(self::END_DATE));

        $this->add(InputFactory::basic(self::SEARCH_TERM));

        $this->add(InputFactory::numeric(self::PAGE));
        $this->add(InputFactory::numeric(self::ITEMS_PER_PAGE, Paginator::ALL_ITEMS));

        $this->add(InputFactory::tags(self::TAGS));

        $tagsMode = InputFactory::basic(self::TAGS_MODE);
        $tagsMode->getValidatorChain()->attach(new InArray([
            'haystack' => enumValues(TagsMode::class),
            'strict' => InArray::COMPARE_STRICT,
        ]));
        $this->add($tagsMode);

        $this->add(InputFactory::orderBy(self::ORDER_BY, enumValues(OrderableField::class)));

        $this->add(InputFactory::boolean(self::EXCLUDE_MAX_VISITS_REACHED));
        $this->add(InputFactory::boolean(self::EXCLUDE_PAST_VALID_UNTIL));

        $this->add(InputFactory::basic(self::DOMAIN));
    }
}
