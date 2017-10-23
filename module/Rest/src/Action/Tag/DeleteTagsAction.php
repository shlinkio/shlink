<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Tag;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Zend\Diactoros\Response\EmptyResponse;

class DeleteTagsAction extends AbstractRestAction
{
    /**
     * @var TagServiceInterface
     */
    private $tagService;

    public function __construct(TagServiceInterface $tagService, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->tagService = $tagService;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $query = $request->getQueryParams();
        $tags = isset($query['tags']) ? $query['tags'] : [];

        $this->tagService->deleteTags($tags);
        return new EmptyResponse();
    }
}
