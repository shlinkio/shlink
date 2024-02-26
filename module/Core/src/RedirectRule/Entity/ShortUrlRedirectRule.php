<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

use function Shlinkio\Shlink\Core\ArrayUtils\every;

class ShortUrlRedirectRule extends AbstractEntity
{
    /**
     * @param Collection<RedirectCondition> $conditions
     */
    public function __construct(
        private readonly ShortUrl $shortUrl, // No need to read this field. It's used by doctrine
        private readonly int $priority,
        public readonly string $longUrl,
        private Collection $conditions = new ArrayCollection(),
    ) {
    }

    /**
     * Tells if this condition matches provided request
     */
    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return $this->conditions->count() > 0 && every(
            $this->conditions,
            static fn (RedirectCondition $condition) => $condition->matchesRequest($request),
        );
    }
}
