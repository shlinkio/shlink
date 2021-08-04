<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Symfony\Component\Console\Input\InputInterface;

final class ShortUrlIdentifier
{
    public function __construct(private string $shortCode, private ?string $domain = null)
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

    public static function fromCli(InputInterface $input): self
    {
        // Using getArguments and getOptions instead of getArgument(...) and getOption(...) because
        // the later throw an exception if requested options are not defined
        /** @var string $shortCode */
        $shortCode = $input->getArguments()['shortCode'] ?? '';
        /** @var string|null $domain */
        $domain = $input->getOptions()['domain'] ?? null;

        return new self($shortCode, $domain);
    }

    public static function fromShortUrl(ShortUrl $shortUrl): self
    {
        $domain = $shortUrl->getDomain();
        $domainAuthority = $domain?->getAuthority();

        return new self($shortUrl->getShortCode(), $domainAuthority);
    }

    public static function fromShortCodeAndDomain(string $shortCode, ?string $domain = null): self
    {
        return new self($shortCode, $domain);
    }

    public function shortCode(): string
    {
        return $this->shortCode;
    }

    public function domain(): ?string
    {
        return $this->domain;
    }
}
