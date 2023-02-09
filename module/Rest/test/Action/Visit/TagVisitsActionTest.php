<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Visit;

use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\VisitsStatsHelperInterface;
use Shlinkio\Shlink\Rest\Action\Visit\TagVisitsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class TagVisitsActionTest extends TestCase
{
    private TagVisitsAction $action;
    private MockObject & VisitsStatsHelperInterface $visitsHelper;

    protected function setUp(): void
    {
        $this->visitsHelper = $this->createMock(VisitsStatsHelperInterface::class);
        $this->action = new TagVisitsAction($this->visitsHelper);
    }

    #[Test]
    public function providingCorrectTagReturnsVisits(): void
    {
        $tag = 'foo';
        $apiKey = ApiKey::create();
        $this->visitsHelper->expects($this->once())->method('visitsForTag')->with(
            $tag,
            $this->isInstanceOf(VisitsParams::class),
            $apiKey,
        )->willReturn(new Paginator(new ArrayAdapter([])));

        $response = $this->action->handle(
            ServerRequestFactory::fromGlobals()->withAttribute('tag', $tag)->withAttribute(ApiKey::class, $apiKey),
        );

        self::assertEquals(200, $response->getStatusCode());
    }
}
