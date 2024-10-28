<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;

use function Shlinkio\Shlink\Core\ipAddressFromRequest;
use function Shlinkio\Shlink\Core\isCrawler;
use function substr;

final class Visitor
{
    public const USER_AGENT_MAX_LENGTH = 512;
    public const REFERER_MAX_LENGTH = 1024;
    public const REMOTE_ADDRESS_MAX_LENGTH = 256;
    public const VISITED_URL_MAX_LENGTH = 2048;

    public readonly string $userAgent;
    public readonly string $referer;
    public readonly string $visitedUrl;
    public readonly string|null $remoteAddress;
    private bool $potentialBot;

    public function __construct(string $userAgent, string $referer, string|null $remoteAddress, string $visitedUrl)
    {
        $this->userAgent = $this->cropToLength($userAgent, self::USER_AGENT_MAX_LENGTH);
        $this->referer = $this->cropToLength($referer, self::REFERER_MAX_LENGTH);
        $this->visitedUrl = $this->cropToLength($visitedUrl, self::VISITED_URL_MAX_LENGTH);
        $this->remoteAddress = $remoteAddress === null ? null : $this->cropToLength(
            $remoteAddress,
            self::REMOTE_ADDRESS_MAX_LENGTH,
        );
        $this->potentialBot = isCrawler($userAgent);
    }

    private function cropToLength(string $value, int $length): string
    {
        return substr($value, 0, $length);
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        return new self(
            $request->getHeaderLine('User-Agent'),
            $request->getHeaderLine('Referer'),
            ipAddressFromRequest($request),
            $request->getUri()->__toString(),
        );
    }

    public static function emptyInstance(): self
    {
        return new self('', '', null, '');
    }

    public static function botInstance(): self
    {
        return new self('cf-facebook', '', null, '');
    }

    public function isPotentialBot(): bool
    {
        return $this->potentialBot;
    }

    public function normalizeForTrackingOptions(TrackingOptions $options): self
    {
        $instance = new self(
            $options->disableUaTracking ? '' : $this->userAgent,
            $options->disableReferrerTracking ? '' : $this->referer,
            $options->disableIpTracking ? null : $this->remoteAddress,
            $this->visitedUrl,
        );

        // Keep the fact that the visit was a potential bot, even if we no longer save the user agent
        $instance->potentialBot = $this->potentialBot;

        return $instance;
    }
}
