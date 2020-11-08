<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\ServerRequest;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Paginator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Shlinkio\Shlink\Rest\Action\Visit\TagVisitsAction;

class TagVisitsActionTest extends TestCase
{
    use ProphecyTrait;

    private TagVisitsAction $action;
    private ObjectProphecy $visitsTracker;

    protected function setUp(): void
    {
        $this->visitsTracker = $this->prophesize(VisitsTracker::class);
        $this->action = new TagVisitsAction($this->visitsTracker->reveal());
    }

    /** @test */
    public function providingCorrectShortCodeReturnsVisits(): void
    {
        $tag = 'foo';
        $getVisits = $this->visitsTracker->visitsForTag($tag, Argument::type(VisitsParams::class))->willReturn(
            new Paginator(new ArrayAdapter([])),
        );

        $response = $this->action->handle((new ServerRequest())->withAttribute('tag', $tag));

        self::assertEquals(200, $response->getStatusCode());
        $getVisits->shouldHaveBeenCalledOnce();
    }
}
