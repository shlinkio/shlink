<?php
namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\DeleteTagsAction;
use Zend\Diactoros\ServerRequestFactory;

class DeleteTagsActionTest extends TestCase
{
    /**
     * @var DeleteTagsAction
     */
    private $action;
    /**
     * @var ObjectProphecy
     */
    private $tagService;

    public function setUp()
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->action = new DeleteTagsAction($this->tagService->reveal());
    }

    /**
     * @test
     * @dataProvider provideTags
     * @param array|null $tags
     */
    public function processDelegatesIntoService($tags)
    {
        $request = ServerRequestFactory::fromGlobals()->withQueryParams(['tags' => $tags]);
        /** @var MethodProphecy $deleteTags */
        $deleteTags = $this->tagService->deleteTags($tags ?: []);

        $response = $this->action->process($request, $this->prophesize(DelegateInterface::class)->reveal());

        $this->assertEquals(204, $response->getStatusCode());
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
