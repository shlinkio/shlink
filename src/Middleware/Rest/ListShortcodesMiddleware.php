<?php
namespace Acelaya\UrlShortener\Middleware\Rest;

use Acelaya\UrlShortener\Paginator\Util\PaginatorSerializerTrait;
use Acelaya\UrlShortener\Service\ShortUrlService;
use Acelaya\UrlShortener\Service\ShortUrlServiceInterface;
use Acelaya\UrlShortener\Util\RestUtils;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stdlib\ArrayUtils;

class ListShortcodesMiddleware extends AbstractRestMiddleware
{
    use PaginatorSerializerTrait;

    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;

    /**
     * ListShortcodesMiddleware constructor.
     * @param ShortUrlServiceInterface|ShortUrlService $shortUrlService
     *
     * @Inject({ShortUrlService::class})
     */
    public function __construct(ShortUrlServiceInterface $shortUrlService)
    {
        $this->shortUrlService = $shortUrlService;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable|null $out
     * @return null|Response
     */
    public function dispatch(Request $request, Response $response, callable $out = null)
    {
        try {
            $query = $request->getQueryParams();
            $shortUrls = $this->shortUrlService->listShortUrls(isset($query['page']) ? $query['page'] : 1);
            return new JsonResponse(['shortUrls' => $this->serializePaginator($shortUrls)]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => 'Unexpected error occured',
            ], 500);
        }
    }
}
