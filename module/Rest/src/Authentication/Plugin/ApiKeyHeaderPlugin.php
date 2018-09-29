<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication\Plugin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\I18n\Translator\TranslatorInterface;

class ApiKeyHeaderPlugin implements AuthenticationPluginInterface
{
    public const HEADER_NAME = 'X-Api-Key';

    /**
     * @var ApiKeyServiceInterface
     */
    private $apiKeyService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ApiKeyServiceInterface $apiKeyService, TranslatorInterface $translator)
    {
        $this->apiKeyService = $apiKeyService;
        $this->translator = $translator;
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
            $this->translator->translate('Provided API key does not exist or is invalid.')
        );
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
