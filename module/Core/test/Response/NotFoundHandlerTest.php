<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Response;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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
     * @test
     * @dataProvider provideResponses
     */
    public function properResponseTypeIsReturned(string $expectedResponse, string $accept, int $renderCalls): void
    {
        $request = (new ServerRequest())->withHeader('Accept', $accept);
        $render = $this->renderer->render(Argument::cetera())->willReturn('');

        $resp = $this->delegate->handle($request);

        $this->assertInstanceOf($expectedResponse, $resp);
        $render->shouldHaveBeenCalledTimes($renderCalls);
    }

    public function provideResponses(): iterable
    {
        yield 'application/json' => [Response\JsonResponse::class, 'application/json', 0];
        yield 'text/json' => [Response\JsonResponse::class, 'text/json', 0];
        yield 'application/x-json' => [Response\JsonResponse::class, 'application/x-json', 0];
        yield 'text/html' => [Response\HtmlResponse::class, 'text/html', 1];
    }
}
