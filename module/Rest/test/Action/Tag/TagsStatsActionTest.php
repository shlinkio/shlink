<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\TagsStatsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function count;

class TagsStatsActionTest extends TestCase
{
    private TagsStatsAction $action;
    private MockObject & TagServiceInterface $tagService;

    protected function setUp(): void
    {
        $this->tagService = $this->createMock(TagServiceInterface::class);
        $this->action = new TagsStatsAction($this->tagService);
    }

    #[Test]
    public function returnsTagsStatsWhenRequested(): void
    {
        $stats = [
            new TagInfo('foo', 1, 1),
            new TagInfo('bar', 3, 10),
        ];
        $itemsCount = count($stats);
        $this->tagService->expects($this->once())->method('tagsInfo')->with(
            $this->anything(),
            $this->isInstanceOf(ApiKey::class),
        )->willReturn(new Paginator(new ArrayAdapter($stats)));
        $req = $this->requestWithApiKey()->withQueryParams(['withStats' => 'true']);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle($req);
        $payload = $resp->getPayload();

        self::assertEquals([
            'tags' => [
                'data' => $stats,
                'pagination' => [
                    'currentPage' => 1,
                    'pagesCount' => 1,
                    'itemsPerPage' => 10,
                    'itemsInCurrentPage' => $itemsCount,
                    'totalItems' => $itemsCount,
                ],
            ],
        ], $payload);
    }

    private function requestWithApiKey(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, ApiKey::create());
    }
}
