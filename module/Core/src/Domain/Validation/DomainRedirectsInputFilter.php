<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Validation;

use Laminas\InputFilter\InputFilter;
use Shlinkio\Shlink\Common\Validation\HostAndPortValidator;
use Shlinkio\Shlink\Common\Validation\InputFactory;

/** @extends InputFilter<mixed> */
class DomainRedirectsInputFilter extends InputFilter
{
    public const DOMAIN = 'domain';
    public const BASE_URL_REDIRECT = 'baseUrlRedirect';
    public const REGULAR_404_REDIRECT = 'regular404Redirect';
    public const INVALID_SHORT_URL_REDIRECT = 'invalidShortUrlRedirect';

    private function __construct()
    {
    }

    public static function withData(array $data): self
    {
        $instance = new self();

        $instance->initializeInputs();
        $instance->setData($data);

        return $instance;
    }

    private function initializeInputs(): void
    {
        $domain = InputFactory::basic(self::DOMAIN, required: true);
        $domain->getValidatorChain()->attach(new HostAndPortValidator());
        $this->add($domain);

        $this->add(InputFactory::basic(self::BASE_URL_REDIRECT));
        $this->add(InputFactory::basic(self::REGULAR_404_REDIRECT));
        $this->add(InputFactory::basic(self::INVALID_SHORT_URL_REDIRECT));
    }
}
