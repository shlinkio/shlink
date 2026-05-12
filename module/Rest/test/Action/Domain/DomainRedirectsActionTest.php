<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Domain;

use CuyZ\Valinor\MapperBuilder;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Rest\Action\Domain\DomainRedirectsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DomainRedirectsActionTest extends TestCase
{
    private DomainRedirectsAction $action;
    private MockObject & DomainServiceInterface $domainService;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainServiceInterface::class);
        $this->action = new DomainRedirectsAction($this->domainService, new MapperBuilder()->mapper());
    }

    #[Test]
    public function domainIsFetchedAndUsedToGetItConfigured(): void
    {
        $authority = 's.test.com';
        $notFoundRedirects = NotFoundRedirects::withRedirects('foo', 'bar', 'baz');
        $domain = Domain::withAuthority($authority);
        $domain->configureNotFoundRedirects($notFoundRedirects);
        $apiKey = ApiKey::create();
        $request = ServerRequestFactory::fromGlobals()->withParsedBody(['domain' => $authority])
                                                      ->withAttribute(ApiKey::class, $apiKey);

        $this->domainService->expects($this->once())->method('getOrCreate')->with($authority)->willReturn($domain);
        $this->domainService->expects($this->once())->method('configureNotFoundRedirects')->with(
            $authority,
            $notFoundRedirects,
            $apiKey,
        );

        /** @var JsonResponse $response */
        $response = $this->action->handle($request);

        self::assertEquals($notFoundRedirects, $response->getPayload());
    }
}
