<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtils;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class ListTagsAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/tags';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(private readonly TagServiceInterface $tagService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = TagsParams::fromRawData($request->getQueryParams());
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        return new JsonResponse([
            'tags' => PagerfantaUtils::serializePaginator($this->tagService->listTags($params, $apiKey)),
        ]);
    }
}
