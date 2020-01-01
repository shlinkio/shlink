<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Middleware\IpAddressMiddlewareFactory;

use function substr;

final class Visitor
{
    public const USER_AGENT_MAX_LENGTH = 512;
    public const REFERER_MAX_LENGTH = 1024;
    public const REMOTE_ADDRESS_MAX_LENGTH = 256;

    private string $userAgent;
    private string $referer;
    private ?string $remoteAddress;

    public function __construct(string $userAgent, string $referer, ?string $remoteAddress)
    {
        $this->userAgent = $this->cropToLength($userAgent, self::USER_AGENT_MAX_LENGTH);
        $this->referer = $this->cropToLength($referer, self::REFERER_MAX_LENGTH);
        $this->remoteAddress = $this->cropToLength($remoteAddress, self::REMOTE_ADDRESS_MAX_LENGTH);
    }

    private function cropToLength(?string $value, int $length): ?string
    {
        return $value === null ? null : substr($value, 0, $length);
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        return new self(
            $request->getHeaderLine('User-Agent'),
            $request->getHeaderLine('Referer'),
            $request->getAttribute(IpAddressMiddlewareFactory::REQUEST_ATTR),
        );
    }

    public static function emptyInstance(): self
    {
        return new self('', '', null);
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getReferer(): string
    {
        return $this->referer;
    }

    public function getRemoteAddress(): ?string
    {
        return $this->remoteAddress;
    }
}
