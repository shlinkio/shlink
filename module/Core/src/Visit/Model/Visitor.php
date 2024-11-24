<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Model;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

use function Shlinkio\Shlink\Core\geolocationFromRequest;
use function Shlinkio\Shlink\Core\ipAddressFromRequest;
use function Shlinkio\Shlink\Core\isCrawler;
use function substr;
use const Shlinkio\Shlink\REDIRECT_URL_REQUEST_ATTRIBUTE;

final readonly class Visitor
{
    public const USER_AGENT_MAX_LENGTH = 512;
    public const REFERER_MAX_LENGTH = 1024;
    public const REMOTE_ADDRESS_MAX_LENGTH = 256;
    public const VISITED_URL_MAX_LENGTH = 2048;
    public const REDIRECT_URL_MAX_LENGTH = 2048;

    private function __construct(
        public string $userAgent,
        public string $referer,
        public string|null $remoteAddress,
        public string $visitedUrl,
        public bool $potentialBot,
        public Location|null $geolocation,
        public string|null $redirectUrl,
    ) {
    }

    public static function fromParams(
        string $userAgent = '',
        string $referer = '',
        string|null $remoteAddress = null,
        string $visitedUrl = '',
        Location|null $geolocation = null,
        string|null $redirectUrl = null,
    ): self {
        return new self(
            userAgent: self::cropToLength($userAgent, self::USER_AGENT_MAX_LENGTH),
            referer: self::cropToLength($referer, self::REFERER_MAX_LENGTH),
            remoteAddress: $remoteAddress === null
                ? null
                : self::cropToLength($remoteAddress, self::REMOTE_ADDRESS_MAX_LENGTH),
            visitedUrl: self::cropToLength($visitedUrl, self::VISITED_URL_MAX_LENGTH),
            potentialBot: isCrawler($userAgent),
            geolocation: $geolocation,
            redirectUrl: $redirectUrl === null ? null : self::cropToLength($redirectUrl, self::REDIRECT_URL_MAX_LENGTH),
        );
    }

    private static function cropToLength(string $value, int $length): string
    {
        return substr($value, 0, $length);
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        return self::fromParams(
            userAgent: $request->getHeaderLine('User-Agent'),
            referer: $request->getHeaderLine('Referer'),
            remoteAddress: ipAddressFromRequest($request),
            visitedUrl: $request->getUri()->__toString(),
            geolocation: geolocationFromRequest($request),
            redirectUrl: $request->getAttribute(REDIRECT_URL_REQUEST_ATTRIBUTE),
        );
    }

    public static function empty(): self
    {
        return self::fromParams();
    }

    public static function botInstance(): self
    {
        return self::fromParams(userAgent: 'cf-facebook');
    }

    public function normalizeForTrackingOptions(TrackingOptions $options): self
    {
        return new self(
            userAgent: $options->disableUaTracking ? '' : $this->userAgent,
            referer: $options->disableReferrerTracking ? '' : $this->referer,
            remoteAddress: $options->disableIpTracking ? null : $this->remoteAddress,
            visitedUrl: $this->visitedUrl,
            // Keep the fact that the visit was a potential bot, even if we no longer save the user agent
            potentialBot: $this->potentialBot,
            geolocation: $this->geolocation,
            redirectUrl: $this->redirectUrl,
        );
    }
}
