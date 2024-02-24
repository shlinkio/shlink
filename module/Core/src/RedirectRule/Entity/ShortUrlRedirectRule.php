<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

class ShortUrlRedirectRule extends AbstractEntity
{
    /**
     * @param Collection<RedirectCondition> $conditions
     */
    public function __construct(
        private readonly ShortUrl $shortUrl, // No need to read this field. It's used by doctrine
        public readonly int $priority,
        public readonly string $longUrl,
        public readonly Collection $conditions = new ArrayCollection(),
    ) {
    }
}
