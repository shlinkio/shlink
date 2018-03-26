<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\ListTagsAction;
use Zend\Diactoros\ServerRequestFactory;

class ListTagsActionTest extends TestCase
{
    /**
     * @var ListTagsAction
     */
    private $action;
    /**
     * @var ObjectProphecy
     */
    private $tagService;

    public function setUp()
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->action = new ListTagsAction($this->tagService->reveal());
    }

    /**
     * @test
     */
    public function returnsDataFromService()
    {
        /** @var MethodProphecy $listTags */
        $listTags = $this->tagService->listTags()->willReturn([new Tag('foo'), new Tag('bar')]);

        $resp = $this->action->handle(ServerRequestFactory::fromGlobals());

        $this->assertEquals([
            'tags' => [
                'data' => ['foo', 'bar'],
            ],
        ], \json_decode((string) $resp->getBody(), true));
        $listTags->shouldHaveBeenCalled();
    }
}
