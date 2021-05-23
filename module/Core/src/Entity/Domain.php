<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use JsonSerializable;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;

class Domain extends AbstractEntity implements JsonSerializable
{
    public function __construct(private string $authority)
    {
    }

    public function getAuthority(): string
    {
        return $this->authority;
    }

    public function jsonSerialize(): string
    {
        return $this->getAuthority();
    }
}
