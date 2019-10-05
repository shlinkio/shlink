<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\CreateTagsAction;
use Zend\Diactoros\ServerRequest;

class CreateTagsActionTest extends TestCase
{
    /** @var CreateTagsAction */
    private $action;
    /** @var ObjectProphecy */
    private $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->action = new CreateTagsAction($this->tagService->reveal());
    }

    /**
     * @test
     * @dataProvider provideTags
     */
    public function processDelegatesIntoService(?array $tags): void
    {
        $request = (new ServerRequest())->withParsedBody(['tags' => $tags]);
        $deleteTags = $this->tagService->createTags($tags ?: [])->willReturn(new ArrayCollection());

        $response = $this->action->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $deleteTags->shouldHaveBeenCalled();
    }

    public function provideTags(): iterable
    {
        yield 'three tags' => [['foo', 'bar', 'baz']];
        yield 'two tags' => [['some', 'thing']];
        yield 'null tags' => [null];
        yield 'empty tags' => [[]];
    }
}
