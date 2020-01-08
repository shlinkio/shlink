<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication\Plugin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;

class ApiKeyHeaderPlugin implements AuthenticationPluginInterface
{
    public const HEADER_NAME = 'X-Api-Key';

    private ApiKeyServiceInterface $apiKeyService;

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
        if (! $this->apiKeyService->check($apiKey)) {
            throw VerifyAuthenticationException::forInvalidApiKey();
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
