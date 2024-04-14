<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;

class OrphanVisitsCount extends AbstractEntity
{
    public function __construct(
        public readonly bool $potentialBot = false,
        public readonly int $slotId = 1,
        public readonly string $count = '1',
    ) {
    }
}
