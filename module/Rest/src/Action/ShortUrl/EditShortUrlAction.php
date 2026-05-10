<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use CuyZ\Valinor\Mapper\TreeMapper;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformerInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

use function array_key_exists;

class EditShortUrlAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/short-urls/{shortCode}';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_PATCH];

    public function __construct(
        private readonly ShortUrlServiceInterface $shortUrlService,
        private readonly ShortUrlDataTransformerInterface $transformer,
        private readonly TreeMapper $treeMapper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $shortUrlEdit = $this->treeMapper->map(ShortUrlEdition::class, [
            ...$body,
            'titleWasProvided' => array_key_exists('title', $body),
        ]);

        $identifier = ShortUrlIdentifier::fromApiRequest($request);
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        $shortUrl = $this->shortUrlService->updateShortUrl($identifier, $shortUrlEdit, $apiKey);

        return new JsonResponse($this->transformer->transform($shortUrl));
    }
}
