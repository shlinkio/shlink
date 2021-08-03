<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Domain\Request;

use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\Validation\DomainRedirectsInputFilter;
use Shlinkio\Shlink\Core\Exception\ValidationException;

use function array_key_exists;

class DomainRedirectsRequest
{
    private string $authority;
    private ?string $baseUrlRedirect = null;
    private bool $baseUrlRedirectWasProvided = false;
    private ?string $regular404Redirect = null;
    private bool $regular404RedirectWasProvided = false;
    private ?string $invalidShortUrlRedirect = null;
    private bool $invalidShortUrlRedirectWasProvided = false;

    private function __construct()
    {
    }

    public static function fromRawData(array $payload): self
    {
        $instance = new self();
        $instance->validateAndInit($payload);
        return $instance;
    }

    /**
     * @throws ValidationException
     */
    private function validateAndInit(array $payload): void
    {
        $inputFilter = DomainRedirectsInputFilter::withData($payload);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        $this->baseUrlRedirectWasProvided = array_key_exists(
            DomainRedirectsInputFilter::BASE_URL_REDIRECT,
            $payload,
        );
        $this->regular404RedirectWasProvided = array_key_exists(
            DomainRedirectsInputFilter::REGULAR_404_REDIRECT,
            $payload,
        );
        $this->invalidShortUrlRedirectWasProvided = array_key_exists(
            DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT,
            $payload,
        );

        $this->authority = $inputFilter->getValue(DomainRedirectsInputFilter::DOMAIN);
        $this->baseUrlRedirect = $inputFilter->getValue(DomainRedirectsInputFilter::BASE_URL_REDIRECT);
        $this->regular404Redirect = $inputFilter->getValue(DomainRedirectsInputFilter::REGULAR_404_REDIRECT);
        $this->invalidShortUrlRedirect = $inputFilter->getValue(DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT);
    }

    public function authority(): string
    {
        return $this->authority;
    }

    public function toNotFoundRedirects(?NotFoundRedirectConfigInterface $defaults = null): NotFoundRedirects
    {
        return NotFoundRedirects::withRedirects(
            $this->baseUrlRedirectWasProvided ? $this->baseUrlRedirect : $defaults?->baseUrlRedirect(),
            $this->regular404RedirectWasProvided ? $this->regular404Redirect : $defaults?->regular404Redirect(),
            $this->invalidShortUrlRedirectWasProvided
                ? $this->invalidShortUrlRedirect
                : $defaults?->invalidShortUrlRedirect(),
        );
    }
}
