<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;

use function Functional\map;

class ListDomainsAction extends AbstractRestAction
{
    protected const ROUTE_PATH = '/domains';
    protected const ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    private DomainServiceInterface $domainService;
    private string $defaultDomain;

    public function __construct(DomainServiceInterface $domainService, string $defaultDomain)
    {
        $this->domainService = $domainService;
        $this->defaultDomain = $defaultDomain;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $regularDomains = $this->domainService->listDomainsWithout($this->defaultDomain);

        return new JsonResponse([
            'domains' => [
                'data' => [
                    $this->mapDomain($this->defaultDomain, true),
                    ...map($regularDomains, fn (Domain $domain) => $this->mapDomain($domain->getAuthority())),
                ],
            ],
        ]);
    }

    private function mapDomain(string $domain, bool $isDefault = false): array
    {
        return [
            'domain' => $domain,
            'isDefault' => $isDefault,
        ];
    }
}
