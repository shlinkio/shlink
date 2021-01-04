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

class ListDomainsActionTest extends TestCase
{
    use ProphecyTrait;

    private ListDomainsAction $action;
    private ObjectProphecy $domainService;

    public function setUp(): void
    {
        $this->domainService = $this->prophesize(DomainServiceInterface::class);
        $this->action = new ListDomainsAction($this->domainService->reveal(), 'foo.com');
    }

    /** @test */
    public function domainsAreProperlyListed(): void
    {
        $domains = [
            new DomainItem('bar.com', true),
            new DomainItem('baz.com', false),
        ];
        $listDomains = $this->domainService->listDomains()->willReturn($domains);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(ServerRequestFactory::fromGlobals());
        $payload = $resp->getPayload();

        self::assertEquals([
            'domains' => [
                'data' => $domains,
            ],
        ], $payload);
        $listDomains->shouldHaveBeenCalledOnce();
    }
}
