<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Throwable;

final readonly class UrlShorteningResult
{
    private function __construct(
        public ShortUrl $shortUrl,
        private Throwable|null $errorOnEventDispatching,
    ) {
    }

    /**
     * @param callable(Throwable $errorOnEventDispatching): mixed $handler
     */
    public function onEventDispatchingError(callable $handler): void
    {
        if ($this->errorOnEventDispatching !== null) {
            $handler($this->errorOnEventDispatching);
        }
    }

    public static function withoutErrorOnEventDispatching(ShortUrl $shortUrl): self
    {
        return new self($shortUrl, null);
    }

    public static function withErrorOnEventDispatching(ShortUrl $shortUrl, Throwable $errorOnEventDispatching): self
    {
        return new self($shortUrl, $errorOnEventDispatching);
    }
}
