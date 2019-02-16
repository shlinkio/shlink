<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Response;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Response\NotFoundHandler;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Template\TemplateRendererInterface;

class NotFoundHandlerTest extends TestCase
{
    /** @var NotFoundHandler */
    private $delegate;
    /** @var ObjectProphecy */
    private $renderer;

    public function setUp(): void
    {
        $this->renderer = $this->prophesize(TemplateRendererInterface::class);
        $this->delegate = new NotFoundHandler($this->renderer->reveal());
    }

    /**
     * @param string $expectedResponse
     * @param string $accept
     * @param int $renderCalls
     *
     * @test
     * @dataProvider provideResponses
     */
    public function properResponseTypeIsReturned(string $expectedResponse, string $accept, int $renderCalls)
    {
        $request = (new ServerRequest())->withHeader('Accept', $accept);
        /** @var MethodProphecy $render */
        $render = $this->renderer->render(Argument::cetera())->willReturn('');

        $resp = $this->delegate->handle($request);

        $this->assertInstanceOf($expectedResponse, $resp);
        $render->shouldHaveBeenCalledTimes($renderCalls);
    }

    public function provideResponses(): array
    {
        return [
            [Response\JsonResponse::class, 'application/json', 0],
            [Response\JsonResponse::class, 'text/json', 0],
            [Response\JsonResponse::class, 'application/x-json', 0],
            [Response\HtmlResponse::class, 'text/html', 1],
        ];
    }
}
