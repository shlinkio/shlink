<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Zend\Diactoros\Response\JsonResponse;

class EditShortUrlTagsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/short-urls/{shortCode}/tags';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_PUT];

    /** @var ShortUrlServiceInterface */
    private $shortUrlService;

    public function __construct(ShortUrlServiceInterface $shortUrlService, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->shortUrlService = $shortUrlService;
    }

    public function handle(Request $request): Response
    {
        $shortCode = $request->getAttribute('shortCode');
        $bodyParams = $request->getParsedBody();

        if (! isset($bodyParams['tags'])) {
            throw ValidationException::fromArray([
                'tags' => 'List of tags has to be provided',
            ]);
        }
        $tags = $bodyParams['tags'];

        $shortUrl = $this->shortUrlService->setTagsByShortCode($shortCode, $tags);
        return new JsonResponse(['tags' => $shortUrl->getTags()->toArray()]);
    }
}
