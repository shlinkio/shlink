<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Entity\Domain;
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
        $listDomains = $this->domainService->listDomainsWithout('foo.com')->willReturn([
            new Domain('bar.com'),
            new Domain('baz.com'),
        ]);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(ServerRequestFactory::fromGlobals());
        $payload = $resp->getPayload();

        self::assertEquals([
            'domains' => [
                'data' => [
                    [
                        'domain' => 'foo.com',
                        'isDefault' => true,
                    ],
                    [
                        'domain' => 'bar.com',
                        'isDefault' => false,
                    ],
                    [
                        'domain' => 'baz.com',
                        'isDefault' => false,
                    ],
                ],
            ],
        ], $payload);
        $listDomains->shouldHaveBeenCalledOnce();
    }
}
