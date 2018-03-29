<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Response;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Response\NotFoundDelegate;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Expressive\Template\TemplateRendererInterface;

class NotFoundDelegateTest extends TestCase
{
    /**
     * @var NotFoundDelegate
     */
    private $delegate;
    /**
     * @var ObjectProphecy
     */
    private $renderer;

    public function setUp()
    {
        $this->renderer = $this->prophesize(TemplateRendererInterface::class);
        $this->delegate = new NotFoundDelegate($this->renderer->reveal());
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
        $request = ServerRequestFactory::fromGlobals()->withHeader('Accept', $accept);
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
