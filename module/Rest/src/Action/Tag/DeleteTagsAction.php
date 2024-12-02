<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class DeleteTagsAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/tags';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_DELETE];

    public function __construct(private readonly TagServiceInterface $tagService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $tags = $query['tags'] ?? [];
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        $this->tagService->deleteTags($tags, $apiKey);
        return new EmptyResponse();
    }
}
