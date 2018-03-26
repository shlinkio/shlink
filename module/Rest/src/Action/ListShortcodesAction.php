<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
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

    public function __construct(
        ShortUrlServiceInterface $shortUrlService,
        TranslatorInterface $translator,
        LoggerInterface $logger = null
    ) {
        parent::__construct($logger);
        $this->shortUrlService = $shortUrlService;
        $this->translator = $translator;
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
            return new JsonResponse(['shortUrls' => $this->serializePaginator($shortUrls)]);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while listing short URLs.' . PHP_EOL . $e);
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
    public function queryToListParams(array $query)
    {
        return [
            $query['page'] ?? 1,
            $query['searchTerm'] ?? null,
            $query['tags'] ?? [],
            $query['orderBy'] ?? null,
        ];
    }
}
