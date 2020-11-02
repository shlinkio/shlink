<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\UpdateTagAction;

class UpdateTagActionTest extends TestCase
{
    use ProphecyTrait;

    private UpdateTagAction $action;
    private ObjectProphecy $tagService;

    public function setUp(): void
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->action = new UpdateTagAction($this->tagService->reveal());
    }

    /**
     * @test
     * @dataProvider provideParams
     */
    public function whenInvalidParamsAreProvidedAnErrorIsReturned(array $bodyParams): void
    {
        $request = (new ServerRequest())->withParsedBody($bodyParams);

        $this->expectException(ValidationException::class);

        $this->action->handle($request);
    }

    public function provideParams(): iterable
    {
        yield 'old name only' => [['oldName' => 'foo']];
        yield 'new name only' => [['newName' => 'foo']];
        yield 'no params' => [[]];
    }

    /** @test */
    public function correctInvocationRenamesTag(): void
    {
        $request = (new ServerRequest())->withParsedBody([
            'oldName' => 'foo',
            'newName' => 'bar',
        ]);
        $rename = $this->tagService->renameTag('foo', 'bar')->willReturn(new Tag('bar'));

        $resp = $this->action->handle($request);

        self::assertEquals(204, $resp->getStatusCode());
        $rename->shouldHaveBeenCalled();
    }
}
