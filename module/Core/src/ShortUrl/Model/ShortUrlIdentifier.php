<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Symfony\Component\Console\Input\InputInterface;

final class ShortUrlIdentifier
{
    private function __construct(public readonly string $shortCode, public readonly ?string $domain = null)
    {
    }

    public static function fromApiRequest(ServerRequestInterface $request): self
    {
        $shortCode = $request->getAttribute('shortCode', '');
        $domain = $request->getQueryParams()['domain'] ?? null;
        $domain = self::overrideDomain($domain);

        return new self($shortCode, $domain);
    }

    public static function fromRedirectRequest(ServerRequestInterface $request): self
    {
        $shortCode = $request->getAttribute('shortCode', '');
        $domain = $request->getUri()->getAuthority();
        $domain = self::overrideDomain($domain);

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
        $domain = self::overrideDomain($domain);

        return new self($shortCode, $domain);
    }

    public static function fromShortUrl(ShortUrl $shortUrl): self
    {
        $domain = $shortUrl->getDomain();
        $domainAuthority = $domain?->getAuthority();
        $domainAuthority = self::overrideDomain($domainAuthority);

        return new self($shortUrl->getShortCode(), $domainAuthority);
    }

    public static function fromShortCodeAndDomain(string $shortCode, ?string $domain = null): self
    {
        return new self($shortCode, $domain);
    }

    public static function overrideDomain(?string $domain = null): ?string
    {
        if ('qa-shortener.salesmsgdev.com' === $domain) {
            $domain = 'salesmsgdev.com';
        }
        if ('shortener.smsg.app' === $domain) {
            $domain = 'smsg.app';
        }
        if ('shortener.salesmsg.io' === $domain) {
            $domain = 'salesmsg.io';
        }
        return $domain;
    }
}
