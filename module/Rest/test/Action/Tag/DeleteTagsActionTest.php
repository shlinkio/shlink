<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\DeleteTagsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DeleteTagsActionTest extends TestCase
{
    use ProphecyTrait;

    private DeleteTagsAction $action;
    private ObjectProphecy $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->action = new DeleteTagsAction($this->tagService->reveal());
    }

    /**
     * @test
     * @dataProvider provideTags
     */
    public function processDelegatesIntoService(?array $tags): void
    {
        $request = (new ServerRequest())
            ->withQueryParams(['tags' => $tags])
            ->withAttribute(ApiKey::class, ApiKey::create());
        $deleteTags = $this->tagService->deleteTags($tags ?: [], Argument::type(ApiKey::class));

        $response = $this->action->handle($request);

        self::assertEquals(204, $response->getStatusCode());
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
