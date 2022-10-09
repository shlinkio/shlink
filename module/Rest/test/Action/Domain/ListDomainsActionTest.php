<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Application;
use Mezzio\Router\Route;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\MiddlewareInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Shlinkio\Shlink\Core\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Rest\Action\Domain\ListDomainsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ListDomainsActionTest extends TestCase
{
    use ProphecyTrait;

    private ListDomainsAction $action;
    private ObjectProphecy $domainService;
    private NotFoundRedirectOptions $options;

    protected function setUp(): void
    {
        $this->domainService = $this->prophesize(DomainServiceInterface::class);
        $this->options = new NotFoundRedirectOptions();
        $this->action = new ListDomainsAction($this->domainService->reveal(), $this->options);
    }

    /** @test */
    public function domainsAreProperlyListed(): void
    {
        $apiKey = ApiKey::create();
        $domains = [
            DomainItem::forDefaultDomain('bar.com', new NotFoundRedirectOptions()),
            DomainItem::forNonDefaultDomain(Domain::withAuthority('baz.com')),
        ];
        $listDomains = $this->domainService->listDomains($apiKey)->willReturn($domains);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, $apiKey));
        $payload = $resp->getPayload();

        self::assertEquals([
            'domains' => [
                'data' => $domains,
                'defaultRedirects' => NotFoundRedirects::fromConfig($this->options),
            ],
        ], $payload);
        $listDomains->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideMiddlewares
     */
    public function routeIsProperlyRegistered(array $extraArgs, array $expectedMiddlewares): void
    {
        $app = $this->prophesize(Application::class);
        $route = $app->route('/rest/v{version:1|2|3}/domains', $expectedMiddlewares, ['GET'], ListDomainsAction::class)
                     ->willReturn(new Route('', $this->prophesize(MiddlewareInterface::class)->reveal()));

        ListDomainsAction::register($app->reveal(), ...$extraArgs);

        $route->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideMiddlewares
     */
    public function unversionedRouteIsProperlyRegistered(array $extraArgs, array $expectedMiddlewares): void
    {
        $app = $this->prophesize(Application::class);
        $route = $app->route(
            '/rest/domains',
            $expectedMiddlewares,
            ['GET'],
            'unversioned_health' . ListDomainsAction::class,
        )->willReturn(new Route('', $this->prophesize(MiddlewareInterface::class)->reveal()));

        ListDomainsAction::registerUnversioned($app->reveal(), ...$extraArgs);

        $route->shouldHaveBeenCalledOnce();
    }

    public function provideMiddlewares(): iterable
    {
        yield 'no middlewares' => [[], [ListDomainsAction::class]];
        yield 'prev middlewares' => [[['foo']], ['foo', ListDomainsAction::class]];
        yield 'next middlewares' => [[[], ['foo']], [ListDomainsAction::class, 'foo']];
        yield 'both middlewares' => [[['bar'], ['foo', 'baz']], ['bar', ListDomainsAction::class, 'foo', 'baz']];
    }
}
