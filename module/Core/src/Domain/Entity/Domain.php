<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Entity;

use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;

class Domain extends AbstractEntity implements JsonSerializable, NotFoundRedirectConfigInterface
{
    public const DEFAULT_AUTHORITY = 'DEFAULT';

    private function __construct(
        public readonly string $authority,
        private string|null $baseUrlRedirect = null,
        private string|null $regular404Redirect = null,
        private string|null $invalidShortUrlRedirect = null,
    ) {
    }

    public static function withAuthority(string $authority): self
    {
        return new self($authority);
    }

    public function jsonSerialize(): string
    {
        return $this->authority;
    }

    public function invalidShortUrlRedirect(): string|null
    {
        return $this->invalidShortUrlRedirect;
    }

    public function hasInvalidShortUrlRedirect(): bool
    {
        return $this->invalidShortUrlRedirect !== null;
    }

    public function regular404Redirect(): string|null
    {
        return $this->regular404Redirect;
    }

    public function hasRegular404Redirect(): bool
    {
        return $this->regular404Redirect !== null;
    }

    public function baseUrlRedirect(): string|null
    {
        return $this->baseUrlRedirect;
    }

    public function hasBaseUrlRedirect(): bool
    {
        return $this->baseUrlRedirect !== null;
    }

    public function configureNotFoundRedirects(NotFoundRedirects $redirects): void
    {
        $this->baseUrlRedirect = $redirects->baseUrlRedirect;
        $this->regular404Redirect = $redirects->regular404Redirect;
        $this->invalidShortUrlRedirect = $redirects->invalidShortUrlRedirect;
    }
}
