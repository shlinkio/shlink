<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Shlinkio\Shlink\Rest\Action\Domain\ListDomainsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ListDomainsActionTest extends TestCase
{
    use ProphecyTrait;

    private ListDomainsAction $action;
    private ObjectProphecy $domainService;

    public function setUp(): void
    {
        $this->domainService = $this->prophesize(DomainServiceInterface::class);
        $this->action = new ListDomainsAction($this->domainService->reveal());
    }

    /** @test */
    public function domainsAreProperlyListed(): void
    {
        $apiKey = ApiKey::create();
        $domains = [
            new DomainItem('bar.com', true),
            new DomainItem('baz.com', false),
        ];
        $listDomains = $this->domainService->listDomains($apiKey)->willReturn($domains);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey));
        $payload = $resp->getPayload();

        self::assertEquals([
            'domains' => [
                'data' => $domains,
            ],
        ], $payload);
        $listDomains->shouldHaveBeenCalledOnce();
    }
}
