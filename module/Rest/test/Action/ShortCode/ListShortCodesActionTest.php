<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortCode;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Rest\Action\ShortCode\ListShortCodesAction;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

class ListShortCodesActionTest extends TestCase
{
    /**
     * @var ListShortCodesAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    protected $service;

    public function setUp()
    {
        $this->service = $this->prophesize(ShortUrlService::class);
        $this->action = new ListShortCodesAction($this->service->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function properListReturnsSuccessResponse()
    {
        $page = 3;
        $this->service->listShortUrls($page, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                            ->shouldBeCalledTimes(1);

        $response = $this->action->handle(ServerRequestFactory::fromGlobals()->withQueryParams([
            'page' => $page,
        ]));
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function anExceptionsReturnsErrorResponse()
    {
        $page = 3;
        $this->service->listShortUrls($page, null, [], null)->willThrow(\Exception::class)
                                                            ->shouldBeCalledTimes(1);

        $response = $this->action->handle(ServerRequestFactory::fromGlobals()->withQueryParams([
            'page' => $page,
        ]));
        $this->assertEquals(500, $response->getStatusCode());
    }
}
