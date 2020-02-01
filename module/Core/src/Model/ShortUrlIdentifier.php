<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\InputInterface;

final class ShortUrlIdentifier
{
    private string $shortCode;
    private ?string $domain;

    public function __construct(string $shortCode, ?string $domain = null)
    {
        $this->shortCode = $shortCode;
        $this->domain = $domain;
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
        $shortCode = $input->getArguments()['shortCode'] ?? '';
        $domain = $input->getOptions()['domain'] ?? null;

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
