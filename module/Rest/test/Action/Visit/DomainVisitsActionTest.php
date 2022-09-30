<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\DomainVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DomainVisitsActionTest extends TestCase
{
    use ProphecyTrait;

    private DomainVisitsAction $action;
    private ObjectProphecy $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->prophesize(VisitsStatsHelperInterface::class);
        $this->action = new DomainVisitsAction($this->visitsHelper->reveal(), 'the_default.com');
    }

    /**
     * @test
     * @dataProvider provideDomainAuthorities
     */
    public function providingCorrectDomainReturnsVisits(string $providedDomain, string $expectedDomain): void
    {
        $apiKey = ApiKey::create();
        $getVisits = $this->visitsHelper->visitsForDomain(
            $expectedDomain,
            Argument::type(VisitsParams::class),
            $apiKey,
        )->willReturn(new Paginator(new ArrayAdapter([])));

        $response = $this->action->handle(
            ServerRequestFactory::fromGlobals()->withAttribute('domain', $providedDomain)
                                               ->withAttribute(ApiKey::class, $apiKey),
        );

        self::assertEquals(200, $response->getStatusCode());
        $getVisits->shouldHaveBeenCalledOnce();
    }

    public function provideDomainAuthorities(): iterable
    {
        yield 'no default domain' => ['foo.com', 'foo.com'];
        yield 'default domain' => ['the_default.com', 'DEFAULT'];
        yield 'DEFAULT keyword' => ['DEFAULT', 'DEFAULT'];
    }
}
