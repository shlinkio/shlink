<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Entity;

use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;

class Domain extends AbstractEntity implements JsonSerializable, NotFoundRedirectConfigInterface
{
    public const string DEFAULT_AUTHORITY = 'DEFAULT';

    private function __construct(
        public readonly string $authority,
        private(set) string|null $baseUrlRedirect = null,
        private(set) string|null $regular404Redirect = null,
        private(set) string|null $invalidShortUrlRedirect = null,
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

    public function configureNotFoundRedirects(NotFoundRedirects $redirects): void
    {
        $this->baseUrlRedirect = $redirects->baseUrlRedirect;
        $this->regular404Redirect = $redirects->regular404Redirect;
        $this->invalidShortUrlRedirect = $redirects->invalidShortUrlRedirect;
    }
}
