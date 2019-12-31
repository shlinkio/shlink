<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Exception\MissingAuthenticationException;

use function array_filter;
use function array_reduce;
use function array_shift;

class RequestToHttpAuthPlugin implements RequestToHttpAuthPluginInterface
{
    // Headers here have to be defined in order of priority.
    // When more than one is matched, the first one to be found will take precedence.
    public const SUPPORTED_AUTH_HEADERS = [
        Plugin\ApiKeyHeaderPlugin::HEADER_NAME,
    ];

    private AuthenticationPluginManagerInterface $authPluginManager;

    public function __construct(AuthenticationPluginManagerInterface $authPluginManager)
    {
        $this->authPluginManager = $authPluginManager;
    }

    /**
     * @throws MissingAuthenticationException
     */
    public function fromRequest(ServerRequestInterface $request): Plugin\AuthenticationPluginInterface
    {
        if (! $this->hasAnySupportedHeader($request)) {
            throw MissingAuthenticationException::fromExpectedTypes(self::SUPPORTED_AUTH_HEADERS);
        }

        return $this->authPluginManager->get($this->getFirstAvailableHeader($request));
    }

    private function hasAnySupportedHeader(ServerRequestInterface $request): bool
    {
        return array_reduce(
            self::SUPPORTED_AUTH_HEADERS,
            fn (bool $carry, string $header) => $carry || $request->hasHeader($header),
            false
        );
    }

    private function getFirstAvailableHeader(ServerRequestInterface $request): string
    {
        $foundHeaders = array_filter(self::SUPPORTED_AUTH_HEADERS, [$request, 'hasHeader']);
        return array_shift($foundHeaders) ?? '';
    }
}
