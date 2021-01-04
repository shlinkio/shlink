<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

use function Functional\map;

class ListTagsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/tags';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    private TagServiceInterface $tagService;

    public function __construct(TagServiceInterface $tagService)
    {
        $this->tagService = $tagService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $withStats = ($query['withStats'] ?? null) === 'true';
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        if (! $withStats) {
            return new JsonResponse([
                'tags' => [
                    'data' => $this->tagService->listTags($apiKey),
                ],
            ]);
        }

        $tagsInfo = $this->tagService->tagsInfo($apiKey);
        $data = map($tagsInfo, fn (TagInfo $info) => (string) $info->tag());

        return new JsonResponse([
            'tags' => [
                'data' => $data,
                'stats' => $tagsInfo,
            ],
        ]);
    }
}
