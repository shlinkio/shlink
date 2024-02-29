<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\RedirectRule\Model;

use Laminas\InputFilter\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\RedirectRule\Model\Validation\RedirectRulesInputFilter;

readonly class RedirectRulesData
{
    private function __construct(public array $rules)
    {
    }

    public static function fromRawData(array $rawData): self
    {
        try {
            $inputFilter = RedirectRulesInputFilter::initialize($rawData);
            if (! $inputFilter->isValid()) {
                throw ValidationException::fromInputFilter($inputFilter);
            }

            return new self($inputFilter->getValue(RedirectRulesInputFilter::REDIRECT_RULES));
        } catch (InvalidArgumentException) {
            throw ValidationException::fromArray(
                [RedirectRulesInputFilter::REDIRECT_RULES => RedirectRulesInputFilter::REDIRECT_RULES],
            );
        }
    }
}
