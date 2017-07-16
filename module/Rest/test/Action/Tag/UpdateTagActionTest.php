<?php
namespace ShlinkioTest\Shlink\Rest\Action\Tag;

use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Shlinkio\Shlink\Rest\Action\Tag\UpdateTagAction;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;

class UpdateTagActionTest extends TestCase
{
    /**
     * @var UpdateTagAction
     */
    private $action;
    /**
     * @var ObjectProphecy
     */
    private $tagService;

    public function setUp()
    {
        $this->tagService = $this->prophesize(TagServiceInterface::class);
        $this->action = new UpdateTagAction($this->tagService->reveal(), Translator::factory([]));
    }

    /**
     * @test
     * @dataProvider provideParams
     * @param array $bodyParams
     */
    public function whenInvalidParamsAreProvidedAnErrorIsReturned(array $bodyParams)
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody($bodyParams);
        $resp = $this->action->process($request, $this->prophesize(DelegateInterface::class)->reveal());

        $this->assertEquals(400, $resp->getStatusCode());
    }

    public function provideParams()
    {
        return [
            [['oldName' => 'foo']],
            [['newName' => 'foo']],
            [[]],
        ];
    }

    /**
     * @test
     */
    public function requestingInvalidTagReturnsError()
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'oldName' => 'foo',
            'newName' => 'bar',
        ]);
        /** @var MethodProphecy $rename */
        $rename = $this->tagService->renameTag('foo', 'bar')->willThrow(EntityDoesNotExistException::class);

        $resp = $this->action->process($request, $this->prophesize(DelegateInterface::class)->reveal());

        $this->assertEquals(404, $resp->getStatusCode());
        $rename->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function correctInvocationRenamesTag()
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody([
            'oldName' => 'foo',
            'newName' => 'bar',
        ]);
        /** @var MethodProphecy $rename */
        $rename = $this->tagService->renameTag('foo', 'bar')->willReturn(new Tag());

        $resp = $this->action->process($request, $this->prophesize(DelegateInterface::class)->reveal());

        $this->assertEquals(204, $resp->getStatusCode());
        $rename->shouldHaveBeenCalled();
    }
}
