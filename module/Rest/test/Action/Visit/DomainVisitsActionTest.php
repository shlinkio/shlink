<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\DomainVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DomainVisitsActionTest extends TestCase
{
    private DomainVisitsAction $action;
    private MockObject & VisitsStatsHelperInterface $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->action = new DomainVisitsAction($this->visitsHelper, 'the_default.com');
    }

    /**
     * @test
     * @dataProvider provideDomainAuthorities
     */
    public function providingCorrectDomainReturnsVisits(string $providedDomain, string $expectedDomain): void
    {
        $apiKey = ApiKey::create();
        $this->visitsHelper->expects($this->once())->method('visitsForDomain')->with(
            $expectedDomain,
            $this->isInstanceOf(VisitsParams::class),
            $apiKey,
        )->willReturn(new Paginator(new ArrayAdapter([])));

        $response = $this->action->handle(
            ServerRequestFactory::fromGlobals()->withAttribute('domain', $providedDomain)
                                               ->withAttribute(ApiKey::class, $apiKey),
        );

        self::assertEquals(200, $response->getStatusCode());
    }

    public function provideDomainAuthorities(): iterable
    {
        yield 'no default domain' => ['foo.com', 'foo.com'];
        yield 'default domain' => ['the_default.com', 'DEFAULT'];
        yield 'DEFAULT keyword' => ['DEFAULT', 'DEFAULT'];
    }
}
