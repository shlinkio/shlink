<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

/** @deprecated */
class CreateTagsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/tags';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_POST];

    public function __construct(private TagServiceInterface $tagService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array $body */
        $body = $request->getParsedBody();
        $tags = $body['tags'] ?? [];

        return new JsonResponse([
            'tags' => [
                'data' => $this->tagService->createTags($tags)->toArray(),
            ],
        ]);
    }
}
