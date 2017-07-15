<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Doctrine\Common\Collections\ArrayCollection;
use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\CreateTagsAction;
use Zend\Diactoros\ServerRequestFactory;

class CreateTagsActionTest extends TestCase
{
    /**
     * @var CreateTagsAction
     */
    private $action;
    /**
     * @var ObjectProphecy
     */
    private $tagService;

    public function setUp()
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->action = new CreateTagsAction($this->tagService->reveal());
    }

    /**
     * @test
     * @dataProvider provideTags
     * @param array|null $tags
     */
    public function processDelegatesIntoService($tags)
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody(['tags' => $tags]);
        /** @var MethodProphecy $deleteTags */
        $deleteTags = $this->tagService->createTags($tags ?: [])->willReturn(new ArrayCollection());

        $response = $this->action->process($request, $this->prophesize(DelegateInterface::class)->reveal());

        $this->assertEquals(200, $response->getStatusCode());
        $deleteTags->shouldHaveBeenCalled();
    }

    public function provideTags()
    {
        return [
            [['foo', 'bar', 'baz']],
            [['some', 'thing']],
            [null],
            [[]],
        ];
    }
}
