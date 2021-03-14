<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\ListTagsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ListTagsActionTest extends TestCase
{
    use ProphecyTrait;

    private ListTagsAction $action;
    private ObjectProphecy $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->action = new ListTagsAction($this->tagService->reveal());
    }

    /**
     * @test
     * @dataProvider provideNoStatsQueries
     */
    public function returnsBaseDataWhenStatsAreNotRequested(array $query): void
    {
        $tags = [new Tag('foo'), new Tag('bar')];
        $listTags = $this->tagService->listTags(Argument::type(ApiKey::class))->willReturn($tags);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle($this->requestWithApiKey()->withQueryParams($query));
        $payload = $resp->getPayload();

        self::assertEquals([
            'tags' => [
                'data' => $tags,
            ],
        ], $payload);
        $listTags->shouldHaveBeenCalled();
    }

    public function provideNoStatsQueries(): iterable
    {
        yield 'no query' => [[]];
        yield 'withStats is false' => [['withStats' => 'withStats']];
        yield 'withStats is something else' => [['withStats' => 'foo']];
    }

    /** @test */
    public function returnsStatsWhenRequested(): void
    {
        $stats = [
            new TagInfo(new Tag('foo'), 1, 1),
            new TagInfo(new Tag('bar'), 3, 10),
        ];
        $tagsInfo = $this->tagService->tagsInfo(Argument::type(ApiKey::class))->willReturn($stats);
        $req = $this->requestWithApiKey()->withQueryParams(['withStats' => 'true']);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle($req);
        $payload = $resp->getPayload();

        self::assertEquals([
            'tags' => [
                'data' => ['foo', 'bar'],
                'stats' => $stats,
            ],
        ], $payload);
        $tagsInfo->shouldHaveBeenCalled();
    }

    private function requestWithApiKey(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, ApiKey::create());
    }
}
