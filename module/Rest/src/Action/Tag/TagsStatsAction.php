<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtilsTrait;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class TagsStatsAction extends AbstractRestAction
{
    use PagerfantaUtilsTrait;

    protected const ROUTE_PATH = '/tags/stats';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(private TagServiceInterface $tagService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = TagsParams::fromRawData($request->getQueryParams());
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);
        $tagsInfo = $this->tagService->tagsInfo($params, $apiKey);

        return new JsonResponse(['tags' => $this->serializePaginator($tagsInfo)]);
    }
}
