<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Psr\Http\Message\ServerRequestInterface;

final class Visitor
{
    public const REMOTE_ADDRESS_ATTR = 'remote_address';

    /** @var string */
    private $userAgent;
    /** @var string */
    private $referer;
    /** @var string|null */
    private $remoteAddress;

    public function __construct(string $userAgent, string $referer, ?string $remoteAddress)
    {
        $this->userAgent = $userAgent;
        $this->referer = $referer;
        $this->remoteAddress = $remoteAddress;
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        return new self(
            $request->getHeaderLine('User-Agent'),
            $request->getHeaderLine('Referer'),
            $request->getAttribute(self::REMOTE_ADDRESS_ATTR)
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
