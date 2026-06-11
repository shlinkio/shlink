<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ObjectMapper;

use Attribute;
use BackedEnum;
use CuyZ\Valinor\Mapper\AsConverter;
use Shlinkio\Shlink\Common\ObjectMapper\MappingError;
use Shlinkio\Shlink\Core\Model\Ordering;

use function implode;
use function is_string;
use function Shlinkio\Shlink\Common\parseOrderBy;
use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\enumValues;
use function sprintf;

#[AsConverter]
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class OrderingConverter
{
    /** @var non-empty-list<string>|null */
    private array|null $validFields;

    /**
     * @param non-empty-list<string>|class-string<BackedEnum>|null $validFieldsOrEnum An enum or list of valid fields
     */
    public function __construct(array|string|null $validFieldsOrEnum = null)
    {
        $this->validFields = is_string($validFieldsOrEnum) ? enumValues($validFieldsOrEnum) : $validFieldsOrEnum;
    }

    public function map(string|null $value): Ordering
    {
        if ($value === null || $value === '') {
            return Ordering::none();
        }

        [$field, $dir] = parseOrderBy($value);
        if ($this->validFields !== null && !contains($field, $this->validFields)) {
            throw MappingError::withBody(
                sprintf('Resolved order field is not one of ["%s"]', implode('", "', $this->validFields)),
            );
        }

        if ($dir !== null && !contains($dir, Ordering::VALID_ORDER_DIRS)) {
            throw MappingError::withBody('Resolved order direction has to be one of ["ASC", "DESC"]');
        }

        return Ordering::fromTuple([$field, $dir]);
    }
}
