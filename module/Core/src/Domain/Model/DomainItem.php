<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Model;

use JsonSerializable;

final class DomainItem implements JsonSerializable
{
    private string $domain;
    private bool $isDefault;

    public function __construct(string $domain, bool $isDefault)
    {
        $this->domain = $domain;
        $this->isDefault = $isDefault;
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
