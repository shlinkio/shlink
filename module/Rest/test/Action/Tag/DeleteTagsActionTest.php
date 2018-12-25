<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\DeleteTagsAction;
use Zend\Diactoros\ServerRequest;

class DeleteTagsActionTest extends TestCase
{
    /** @var DeleteTagsAction */
    private $action;
    /** @var ObjectProphecy */
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
        $request = (new ServerRequest())->withQueryParams(['tags' => $tags]);
        /** @var MethodProphecy $deleteTags */
        $deleteTags = $this->tagService->deleteTags($tags ?: []);

        $response = $this->action->handle($request);

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
