<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

class GlobalVisitsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/visits';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    private VisitsStatsHelperInterface $statsHelper;

    public function __construct(VisitsStatsHelperInterface $statsHelper, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->statsHelper = $statsHelper;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse([
            'visits' => $this->statsHelper->getVisitsStats(),
        ]);
    }
}
