<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Throwable;

final class UrlShorteningResult
{
    private function __construct(
        public readonly ShortUrl $shortUrl,
        private readonly ?Throwable $errorOnEventDispatching,
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
