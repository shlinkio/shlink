<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class ListDomainsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/domains';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(private DomainServiceInterface $domainService, private NotFoundRedirectOptions $options)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);
        $domainItems = $this->domainService->listDomains($apiKey);

        return new JsonResponse([
            'domains' => [
                'data' => $domainItems,
                'defaultRedirects' => NotFoundRedirects::fromConfig($this->options),
            ],
        ]);
    }
}
