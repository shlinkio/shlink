<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication\Plugin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;

class ApiKeyHeaderPlugin implements AuthenticationPluginInterface
{
    public const HEADER_NAME = 'X-Api-Key';

    /** @var ApiKeyServiceInterface */
    private $apiKeyService;

    public function __construct(ApiKeyServiceInterface $apiKeyService)
    {
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * @throws VerifyAuthenticationException
     */
    public function verify(ServerRequestInterface $request): void
    {
        $apiKey = $request->getHeaderLine(self::HEADER_NAME);
        if ($this->apiKeyService->check($apiKey)) {
            return;
        }

        throw VerifyAuthenticationException::withError(
            RestUtils::INVALID_API_KEY_ERROR,
            'Provided API key does not exist or is invalid.'
        );
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
