<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Model;

use JsonSerializable;

final class DomainItem implements JsonSerializable
{
    public function __construct(private string $domain, private bool $isDefault)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'domain' => $this->domain,
            'isDefault' => $this->isDefault,
        ];
    }

    public function toString(): string
    {
        return $this->domain;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }
}
