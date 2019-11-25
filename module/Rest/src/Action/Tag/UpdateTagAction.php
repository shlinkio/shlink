<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Zend\Diactoros\Response\EmptyResponse;

class UpdateTagAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/tags';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_PUT];

    /** @var TagServiceInterface */
    private $tagService;

    public function __construct(TagServiceInterface $tagService, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->tagService = $tagService;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        if (! isset($body['oldName'], $body['newName'])) {
            throw ValidationException::fromArray([
                'oldName' => 'oldName is required',
                'newName' => 'newName is required',
            ]);
        }

        $this->tagService->renameTag($body['oldName'], $body['newName']);
        return new EmptyResponse();
    }
}
