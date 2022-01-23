<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Paginator\Util\PagerfantaUtilsTrait;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

use function Functional\map;

class ListTagsAction extends AbstractRestAction
{
    use PagerfantaUtilsTrait;

    protected const ROUTE_PATH = '/tags';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(private TagServiceInterface $tagService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = TagsParams::fromRawData($request->getQueryParams());
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        if (! $params->withStats()) {
            return new JsonResponse([
                'tags' => $this->serializePaginator($this->tagService->listTags($params, $apiKey)),
            ]);
        }

        // This part is deprecated. To get tags with stats, the /tags/stats endpoint should be used instead
        $tagsInfo = $this->tagService->tagsInfo($params, $apiKey);
        $rawTags = $this->serializePaginator($tagsInfo, null, 'stats');
        $rawTags['data'] = map($tagsInfo, static fn (TagInfo $info) => $info->tag());

        return new JsonResponse(['tags' => $rawTags]);
    }
}
