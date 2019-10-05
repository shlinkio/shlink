<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;

use function sprintf;

class GetVisitsAction extends AbstractRestAction
{
    use PaginatorUtilsTrait;

    protected const ROUTE_PATH = '/short-urls/{shortCode}/visits';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    /** @var VisitsTrackerInterface */
    private $visitsTracker;

    public function __construct(VisitsTrackerInterface $visitsTracker, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->visitsTracker = $visitsTracker;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function handle(Request $request): Response
    {
        $shortCode = $request->getAttribute('shortCode');

        try {
            $visits = $this->visitsTracker->info($shortCode, VisitsParams::fromRawData($request->getQueryParams()));

            return new JsonResponse([
                'visits' => $this->serializePaginator($visits),
            ]);
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('Provided nonexistent short code {e}', ['e' => $e]);
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf('Provided short code %s does not exist', $shortCode),
            ], self::STATUS_NOT_FOUND);
        }
    }
}
