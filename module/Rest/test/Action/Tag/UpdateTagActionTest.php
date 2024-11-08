<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\UpdateTagAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class UpdateTagActionTest extends TestCase
{
    private UpdateTagAction $action;
    private MockObject & TagServiceInterface $tagService;

    protected function setUp(): void
    {
        $this->tagService = $this->createMock(TagServiceInterface::class);
        $this->action = new UpdateTagAction($this->tagService);
    }

    #[Test, DataProvider('provideParams')]
    public function whenInvalidParamsAreProvidedAnErrorIsReturned(array $bodyParams): void
    {
        $request = $this->requestWithApiKey()->withParsedBody($bodyParams);

        $this->expectException(ValidationException::class);

        $this->action->handle($request);
    }

    public static function provideParams(): iterable
    {
        yield 'old name only' => [['oldName' => 'foo']];
        yield 'new name only' => [['newName' => 'foo']];
        yield 'no params' => [[]];
    }

    #[Test]
    public function correctInvocationRenamesTag(): void
    {
        $request = $this->requestWithApiKey()->withParsedBody([
            'oldName' => 'foo',
            'newName' => 'bar',
        ]);
        $this->tagService->expects($this->once())->method('renameTag')->with(
            Renaming::fromNames('foo', 'bar'),
            $this->isInstanceOf(ApiKey::class),
        )->willReturn(new Tag('bar'));

        $resp = $this->action->handle($request);

        self::assertEquals(204, $resp->getStatusCode());
    }

    private function requestWithApiKey(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals()->withAttribute(ApiKey::class, ApiKey::create());
    }
}
