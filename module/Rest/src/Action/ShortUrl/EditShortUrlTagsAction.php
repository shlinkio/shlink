<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

class EditShortUrlTagsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-codes/{shortCode}/tags';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_PUT];

    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ShortUrlServiceInterface $shortUrlService,
        TranslatorInterface $translator,
        LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
        $this->shortUrlService = $shortUrlService;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function handle(Request $request): Response
    {
        $shortCode = $request->getAttribute('shortCode');
        $bodyParams = $request->getParsedBody();

        if (! isset($bodyParams['tags'])) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => $this->translator->translate('A list of tags was not provided'),
            ], self::STATUS_BAD_REQUEST);
        }
        $tags = $bodyParams['tags'];

        try {
            $shortUrl = $this->shortUrlService->setTagsByShortCode($shortCode, $tags);
            return new JsonResponse(['tags' => $shortUrl->getTags()->toArray()]);
        } catch (InvalidShortCodeException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf($this->translator->translate('No URL found for short code "%s"'), $shortCode),
            ], self::STATUS_NOT_FOUND);
        }
    }
}
