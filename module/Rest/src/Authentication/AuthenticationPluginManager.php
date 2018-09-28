<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication;

use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Authentication\Plugin\ApiKeyHeaderPlugin;
use Shlinkio\Shlink\Rest\Authentication\Plugin\AuthorizationHeaderPlugin;
use Shlinkio\Shlink\Rest\Exception\NoAuthenticationException;
use Zend\ServiceManager\AbstractPluginManager;
use function array_filter;
use function array_reduce;
use function array_shift;

class AuthenticationPluginManager extends AbstractPluginManager implements AuthenticationPluginManagerInterface
{
    // Headers here have to be defined in order of priority.
    // When more than one is matched, the first one will take precedence
    public const SUPPORTED_AUTH_HEADERS = [
        ApiKeyHeaderPlugin::HEADER_NAME,
        AuthorizationHeaderPlugin::HEADER_NAME,
    ];

    /**
     * @throws ContainerExceptionInterface
     * @throws NoAuthenticationException
     */
    public function fromRequest(ServerRequestInterface $request): Plugin\AuthenticationPluginInterface
    {
        if (! $this->hasAnySupportedHeader($request)) {
            throw NoAuthenticationException::fromExpectedTypes([
                ApiKeyHeaderPlugin::HEADER_NAME,
                AuthorizationHeaderPlugin::HEADER_NAME,
            ]);
        }

        return $this->get($this->getFirstAvailableHeader($request));
    }

    private function hasAnySupportedHeader(ServerRequestInterface $request): bool
    {
        return array_reduce(
            self::SUPPORTED_AUTH_HEADERS,
            function (bool $carry, string $header) use ($request) {
                return $carry || $request->hasHeader($header);
            },
            false
        );
    }

    private function getFirstAvailableHeader(ServerRequestInterface $request): string
    {
        $foundHeaders = array_filter(self::SUPPORTED_AUTH_HEADERS, [$request, 'hasHeader']);
        return array_shift($foundHeaders) ?? '';
    }
}
