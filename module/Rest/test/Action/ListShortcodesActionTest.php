<?php
namespace ShlinkioTest\Shlink\Rest\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Rest\Action\ListShortcodesAction;
use ShlinkioTest\Shlink\Common\Util\TestUtils;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

class ListShortCodesActionTest extends TestCase
{
    /**
     * @var ListShortcodesAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    protected $service;

    public function setUp()
    {
        $this->service = $this->prophesize(ShortUrlService::class);
        $this->action = new ListShortcodesAction($this->service->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function properListReturnsSuccessResponse()
    {
        $page = 3;
        $this->service->listShortUrls($page, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                            ->shouldBeCalledTimes(1);

        $response = $this->action->process(
            ServerRequestFactory::fromGlobals()->withQueryParams([
                'page' => $page,
            ]),
            TestUtils::createDelegateMock()->reveal()
        );
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

        $response = $this->action->process(
            ServerRequestFactory::fromGlobals()->withQueryParams([
                'page' => $page,
            ]),
            TestUtils::createDelegateMock()->reveal()
        );
        $this->assertEquals(500, $response->getStatusCode());
    }
}
