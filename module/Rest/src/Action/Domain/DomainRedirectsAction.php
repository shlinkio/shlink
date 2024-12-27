<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Action\Domain\Request\DomainRedirectsRequest;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class DomainRedirectsAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/domains/redirects';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_PATCH];

    public function __construct(private readonly DomainServiceInterface $domainService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array $body */
        $body = $request->getParsedBody();
        $requestData = DomainRedirectsRequest::fromRawData($body);
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);

        $authority = $requestData->authority();
        $domain = $this->domainService->getOrCreate($authority);
        $notFoundRedirects = $requestData->toNotFoundRedirects($domain);

        $this->domainService->configureNotFoundRedirects($authority, $notFoundRedirects, $apiKey);

        return new JsonResponse($notFoundRedirects);
    }
}
