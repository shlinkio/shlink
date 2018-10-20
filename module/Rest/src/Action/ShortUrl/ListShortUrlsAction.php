<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\I18n\Translator\TranslatorInterface;

class ListShortUrlsAction extends AbstractRestAction
{
    use PaginatorUtilsTrait;

    protected const ROUTE_PATH = '/short-urls';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var array
     */
    private $domainConfig;

    public function __construct(
        ShortUrlServiceInterface $shortUrlService,
        TranslatorInterface $translator,
        array $domainConfig,
        LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
        $this->shortUrlService = $shortUrlService;
        $this->translator = $translator;
        $this->domainConfig = $domainConfig;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function handle(Request $request): Response
    {
        try {
            $params = $this->queryToListParams($request->getQueryParams());
            $shortUrls = $this->shortUrlService->listShortUrls(...$params);
            return new JsonResponse(['shortUrls' => $this->serializePaginator($shortUrls, new ShortUrlDataTransformer(
                $this->domainConfig
            ))]);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while listing short URLs. {e}', ['e' => $e]);
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => $this->translator->translate('Unexpected error occurred'),
            ], self::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array $query
     * @return array
     */
    private function queryToListParams(array $query): array
    {
        return [
            (int) ($query['page'] ?? 1),
            $query['searchTerm'] ?? null,
            $query['tags'] ?? [],
            $query['orderBy'] ?? null,
        ];
    }
}
