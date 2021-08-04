<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Validation;

use Laminas\InputFilter\InputFilter;
use Shlinkio\Shlink\Common\Validation;

class DomainRedirectsInputFilter extends InputFilter
{
    use Validation\InputFactoryTrait;

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
        $domain = $this->createInput(self::DOMAIN);
        $domain->getValidatorChain()->attach(new Validation\HostAndPortValidator());
        $this->add($domain);

        $this->add($this->createInput(self::BASE_URL_REDIRECT, false));
        $this->add($this->createInput(self::REGULAR_404_REDIRECT, false));
        $this->add($this->createInput(self::INVALID_SHORT_URL_REDIRECT, false));
    }
}
