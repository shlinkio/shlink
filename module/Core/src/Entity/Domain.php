<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;

class Domain extends AbstractEntity
{
    /** @var string */
    private $authority;

    public function __construct(string $authority)
    {
        $this->authority = $authority;
    }

    public function getAuthority(): string
    {
        return $this->authority;
    }
}
