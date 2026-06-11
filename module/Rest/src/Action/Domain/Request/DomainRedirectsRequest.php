<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Domain\Request;

use Shlinkio\Shlink\Common\ObjectMapper\HostAndPortConverter;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Util\NoValue;

final readonly class DomainRedirectsRequest
{
    public string $authority;

    public function __construct(
        #[HostAndPortConverter] string $domain,
        private NoValue|string|null $baseUrlRedirect = NoValue::NO_VALUE,
        private NoValue|string|null $regular404Redirect = NoValue::NO_VALUE,
        private NoValue|string|null $invalidShortUrlRedirect = NoValue::NO_VALUE,
    ) {
        $this->authority = $domain;
    }

    public function toNotFoundRedirects(NotFoundRedirectConfigInterface|null $defaults = null): NotFoundRedirects
    {
        return NotFoundRedirects::withRedirects(
            $this->baseUrlRedirect !== NoValue::NO_VALUE ? $this->baseUrlRedirect : $defaults?->baseUrlRedirect,
            $this->regular404Redirect !== NoValue::NO_VALUE
                ? $this->regular404Redirect
                : $defaults?->regular404Redirect,
            $this->invalidShortUrlRedirect !== NoValue::NO_VALUE
                ? $this->invalidShortUrlRedirect
                : $defaults?->invalidShortUrlRedirect,
        );
    }
}
