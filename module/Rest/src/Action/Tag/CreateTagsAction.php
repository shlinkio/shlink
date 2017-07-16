<?php
namespace Shlinkio\Shlink\Rest\Action\Tag;

use Acelaya\ZsmAnnotatedServices\Annotation as DI;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Service\Tag\TagService;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Zend\Diactoros\Response\JsonResponse;

class CreateTagsAction extends AbstractRestAction
{
    /**
     * @var TagServiceInterface
     */
    private $tagService;

    /**
     * CreateTagsAction constructor.
     * @param TagServiceInterface $tagService
     * @param LoggerInterface|null $logger
     *
     * @DI\Inject({TagService::class, LoggerInterface::class})
     */
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
     * @throws \InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $body = $request->getParsedBody();
        $tags = isset($body['tags']) ? $body['tags'] : [];

        return new JsonResponse([
            'tags' => [
                'data' => $this->tagService->createTags($tags)->toArray(),
            ],
        ]);
    }
}
