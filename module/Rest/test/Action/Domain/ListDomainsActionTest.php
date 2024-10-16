<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Config\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Shlinkio\Shlink\Rest\Action\Domain\ListDomainsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ListDomainsActionTest extends TestCase
{
    private ListDomainsAction $action;
    private MockObject & DomainServiceInterface $domainService;
    private NotFoundRedirectOptions $options;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainServiceInterface::class);
        $this->options = new NotFoundRedirectOptions();
        $this->action = new ListDomainsAction($this->domainService, $this->options);
    }

    #[Test]
    public function domainsAreProperlyListed(): void
    {
        $apiKey = ApiKey::create();
        $domains = [
            DomainItem::forDefaultDomain('bar.com', new NotFoundRedirectOptions()),
            DomainItem::forNonDefaultDomain(Domain::withAuthority('baz.com')),
        ];
        $this->domainService->expects($this->once())->method('listDomains')->with($apiKey)->willReturn($domains);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey));
        $payload = $resp->getPayload();

        self::assertEquals([
            'domains' => [
                'data' => $domains,
                'defaultRedirects' => NotFoundRedirects::fromConfig($this->options),
            ],
        ], $payload);
    }
}
