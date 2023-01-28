<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Model;

use JsonSerializable;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;

final class DomainItem implements JsonSerializable
{
    private function __construct(
        private readonly string $authority,
        public readonly NotFoundRedirectConfigInterface $notFoundRedirectConfig,
        public readonly bool $isDefault,
    ) {
    }

    public static function forNonDefaultDomain(Domain $domain): self
    {
        return new self($domain->authority, $domain, false);
    }

    public static function forDefaultDomain(string $defaultDomain, NotFoundRedirectConfigInterface $config): self
    {
        return new self($defaultDomain, $config, true);
    }

    public function jsonSerialize(): array
    {
        return [
            'domain' => $this->authority,
            'isDefault' => $this->isDefault,
            'redirects' => NotFoundRedirects::fromConfig($this->notFoundRedirectConfig),
        ];
    }

    public function toString(): string
    {
        return $this->authority;
    }
}
