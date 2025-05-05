<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JsonSerializable;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

use function array_values;
use function Shlinkio\Shlink\Core\ArrayUtils\every;

class ShortUrlRedirectRule extends AbstractEntity implements JsonSerializable
{
    /**
     * @param Collection<int, RedirectCondition> $conditions
     */
    public function __construct(
        private readonly ShortUrl $shortUrl, // No need to read this field. It's used by doctrine
        private readonly int $priority,
        public readonly string $longUrl,
        private Collection $conditions = new ArrayCollection(),
    ) {
    }

    public function withPriority(int $newPriority): self
    {
        return new self(
            $this->shortUrl,
            $newPriority,
            $this->longUrl,
            $this->conditions,
        );
    }

    /**
     * Tells if this condition matches provided request
     */
    public function matchesRequest(ServerRequestInterface $request, ShortUrl|null $shortUrl = null): bool
    {
        return $this->conditions->count() > 0 && every(
            $this->conditions,
            static fn (RedirectCondition $condition) => $condition->matchesRequest($request, $shortUrl),
        );
    }

    public function clearConditions(): void
    {
        $this->conditions->clear();
    }

    /**
     * @template R
     * @param callable(RedirectCondition $condition): R $callback
     * @return R[]
     */
    public function mapConditions(callable $callback): array
    {
        return $this->conditions->map($callback(...))->toArray();
    }

    public function jsonSerialize(): array
    {
        return [
            'longUrl' => $this->longUrl,
            'priority' => $this->priority,
            'conditions' => array_values($this->conditions->toArray()),
        ];
    }
}
