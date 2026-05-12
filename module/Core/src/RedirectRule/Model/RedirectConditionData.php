<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\RedirectRule\Model;

use Shlinkio\Shlink\Common\ObjectMapper\MappingError;

use function sprintf;

readonly class RedirectConditionData
{
    public function __construct(
        public RedirectConditionType $type,
        public string|null $matchKey = null,
        public string|null $matchValue = null,
    ) {
        if ($matchValue !== null && ! $type->isValid($matchValue)) {
            throw MappingError::withBody(
                sprintf('"%s" is not a valid value for the "%s" redirect condition', $matchValue, $type->name),
            );
        }
    }
}
