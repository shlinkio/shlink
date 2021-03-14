<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Tag\Model\TagRenaming;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\UpdateTagAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

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
        $request = $this->requestWithApiKey()->withParsedBody($bodyParams);

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
        $request = $this->requestWithApiKey()->withParsedBody([
            'oldName' => 'foo',
            'newName' => 'bar',
        ]);
        $rename = $this->tagService->renameTag(
            TagRenaming::fromNames('foo', 'bar'),
            Argument::type(ApiKey::class),
        )->willReturn(new Tag('bar'));

        $resp = $this->action->handle($request);

        self::assertEquals(204, $resp->getStatusCode());
        $rename->shouldHaveBeenCalled();
    }

    private function requestWithApiKey(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, ApiKey::create());
    }
}
