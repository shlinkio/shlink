<?php

namespace Shlinkio\Shlink\Core\Config\Options;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Core\Config\EnvVars;

use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\splitByComma;
use function strtolower;

final readonly class CorsOptions
{
    private const string ORIGIN_PATTERN = '<origin>';

    /** @var string[]|'*'|'<origin>' */
    public string|array $allowOrigins;

    public function __construct(
        string $allowOrigins = '*',
        public bool $allowCredentials = false,
        public int $maxAge = 3600,
    ) {
        $lowerCaseAllowOrigins = strtolower($allowOrigins);
        $this->allowOrigins = $lowerCaseAllowOrigins === '*' || $lowerCaseAllowOrigins === self::ORIGIN_PATTERN
            ? $lowerCaseAllowOrigins
            : splitByComma($lowerCaseAllowOrigins);
    }

    public static function fromEnv(): self
    {
        return new self(
            allowOrigins: EnvVars::CORS_ALLOW_ORIGIN->loadFromEnv(),
            allowCredentials: EnvVars::CORS_ALLOW_CREDENTIALS->loadFromEnv(),
            maxAge: EnvVars::CORS_MAX_AGE->loadFromEnv(),
        );
    }

    /**
     * Creates a new response which contains the CORS headers that apply to provided request
     */
    public function responseWithCorsHeaders(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->responseWithAllowOrigin($request, $response);
        return $this->allowCredentials ? $response->withHeader('Access-Control-Allow-Credentials', 'true') : $response;
    }

    /**
     * If applicable, a new response with the appropriate Access-Control-Allow-Origin header is returned
     */
    private function responseWithAllowOrigin(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->allowOrigins === '*') {
            return $response->withHeader('Access-Control-Allow-Origin', '*');
        }

        $requestOrigin = $request->getHeaderLine('Origin');
        if (
            // The special <origin> value means we should allow requests from the origin set in the request's Origin
            // header
            $this->allowOrigins === self::ORIGIN_PATTERN
            // If an array of allowed hosts was provided, set Access-Control-Allow-Origin header only if request's
            // Origin header matches one of them
            || contains($requestOrigin, $this->allowOrigins)
        ) {
            return $response->withHeader('Access-Control-Allow-Origin', $requestOrigin);
        }

        return $response;
    }
}
