<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

use function sprintf;

final readonly class ShortUrlIdentifier
{
    private function __construct(public string $shortCode, public ?string $domain = null)
    {
    }

    public static function fromApiRequest(ServerRequestInterface $request): self
    {
        $shortCode = $request->getAttribute('shortCode', '');
        $domain = $request->getQueryParams()['domain'] ?? null;

        return new self($shortCode, $domain);
    }

    public static function fromRedirectRequest(ServerRequestInterface $request): self
    {
        $shortCode = $request->getAttribute('shortCode', '');
        $domain = $request->getUri()->getAuthority();

        return new self($shortCode, $domain);
    }

    public static function fromShortUrl(ShortUrl $shortUrl): self
    {
        $domain = $shortUrl->getDomain();
        $domainAuthority = $domain?->authority;

        return new self($shortUrl->getShortCode(), $domainAuthority);
    }

    public static function fromShortCodeAndDomain(string $shortCode, ?string $domain = null): self
    {
        return new self($shortCode, $domain);
    }

    public function __toString(): string
    {
        if ($this->domain === null) {
            return $this->shortCode;
        }

        return sprintf('%s/%s', $this->domain, $this->shortCode);
    }
}
