<?php
namespace Shlinkio\Shlink\Rest\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Rest\Service\ApiKeyService;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

class AuthenticateAction extends AbstractRestAction
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var ApiKeyService|ApiKeyServiceInterface
     */
    private $apiKeyService;

    /**
     * AuthenticateAction constructor.
     * @param ApiKeyServiceInterface|ApiKeyService $apiKeyService
     * @param TranslatorInterface $translator
     *
     * @Inject({ApiKeyService::class, "translator"})
     */
    public function __construct(ApiKeyServiceInterface $apiKeyService, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->apiKeyService = $apiKeyService;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable|null $out
     * @return null|Response
     */
    public function dispatch(Request $request, Response $response, callable $out = null)
    {
        $authData = $request->getParsedBody();
        if (! isset($authData['apiKey'])) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => $this->translator->translate(
                    'You have to provide a valid API key under the "apiKey" param name.'
                ),
            ], 400);
        }

        // Authenticate using provided API key
        if (! $this->apiKeyService->check($authData['apiKey'])) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_API_KEY_ERROR,
                'message' => $this->translator->translate('Provided API key does not exist or is invalid.'),
            ], 401);
        }

        // TODO Generate a JSON Web Token that will be used for authorization in next requests

        return new JsonResponse(['token' => '']);
    }
}
