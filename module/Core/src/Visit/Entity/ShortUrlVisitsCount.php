<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Entity;

use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

class ShortUrlVisitsCount extends AbstractEntity
{
    public function __construct(
        private readonly ShortUrl $shortUrl,
        private readonly bool $potentialBot = false,
        public readonly int $slotId = 1,
        public readonly string $count = '1',
    ) {
    }
}
