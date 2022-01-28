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
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\TagVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class TagVisitsActionTest extends TestCase
{
    use ProphecyTrait;

    private TagVisitsAction $action;
    private ObjectProphecy $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->prophesize(VisitsStatsHelperInterface::class);
        $this->action = new TagVisitsAction($this->visitsHelper->reveal());
    }

    /** @test */
    public function providingCorrectTagReturnsVisits(): void
    {
        $tag = 'foo';
        $apiKey = ApiKey::create();
        $getVisits = $this->visitsHelper->visitsForTag($tag, Argument::type(VisitsParams::class), $apiKey)->willReturn(
            new Paginator(new ArrayAdapter([])),
        );

        $response = $this->action->handle(
            ServerRequestFactory::fromGlobals()->withAttribute('tag', $tag)->withAttribute(ApiKey::class, $apiKey),
        );

        self::assertEquals(200, $response->getStatusCode());
        $getVisits->shouldHaveBeenCalledOnce();
    }
}
