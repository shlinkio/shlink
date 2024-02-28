<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\RedirectRule\Model;

use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\RedirectRule\Model\Validation\RedirectRulesInputFilter;

readonly class RedirectRulesData
{
    private function __construct(public array $rules)
    {
    }

    public static function fromRawData(array $rawData): self
    {
        $inputFilter = RedirectRulesInputFilter::initialize($rawData);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        return new self($inputFilter->getValue(RedirectRulesInputFilter::REDIRECT_RULES));
    }
}
