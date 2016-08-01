<?php
namespace Shlinkio\Shlink\Rest\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

class ListShortcodesAction extends AbstractRestAction
{
    use PaginatorUtilsTrait;

    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ListShortcodesAction constructor.
     * @param ShortUrlServiceInterface|ShortUrlService $shortUrlService
     * @param TranslatorInterface $translator
     *
     * @Inject({ShortUrlService::class, "translator"})
     */
    public function __construct(ShortUrlServiceInterface $shortUrlService, TranslatorInterface $translator)
    {
        $this->shortUrlService = $shortUrlService;
        $this->translator = $translator;
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
                'message' => $this->translator->translate('Unexpected error occurred'),
            ], 500);
        }
    }
}
