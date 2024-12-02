<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class UpdateTagAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/tags';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_PUT];

    public function __construct(private readonly TagServiceInterface $tagService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array $body */
        $body = $request->getParsedBody();
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        $this->tagService->renameTag(Renaming::fromArray($body), $apiKey);
        return new EmptyResponse();
    }
}
