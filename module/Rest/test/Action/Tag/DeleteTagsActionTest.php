<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\DeleteTagsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class DeleteTagsActionTest extends TestCase
{
    private DeleteTagsAction $action;
    private MockObject & TagServiceInterface $tagService;

    protected function setUp(): void
    {
        $this->tagService = $this->createMock(TagServiceInterface::class);
        $this->action = new DeleteTagsAction($this->tagService);
    }

    #[Test, DataProvider('provideTags')]
    public function processDelegatesIntoService(array|null $tags): void
    {
        $request = (new ServerRequest())
            ->withQueryParams(['tags' => $tags])
            ->withAttribute(ApiKey::class, ApiKey::create());
        $this->tagService->expects($this->once())->method('deleteTags')->with(
            $tags ?? [],
            $this->isInstanceOf(ApiKey::class),
        );

        $response = $this->action->handle($request);

        self::assertEquals(204, $response->getStatusCode());
    }

    public static function provideTags(): iterable
    {
        yield 'three tags' => [['foo', 'bar', 'baz']];
        yield 'two tags' => [['some', 'thing']];
        yield 'null tags' => [null];
        yield 'empty tags' => [[]];
    }
}
