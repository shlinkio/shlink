<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagRenaming;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

class UpdateTagAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/tags';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_PUT];

    private TagServiceInterface $tagService;

    public function __construct(TagServiceInterface $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     *
     * @throws \InvalidArgumentException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $this->tagService->renameTag(TagRenaming::fromArray($body));
        return new EmptyResponse();
    }
}
