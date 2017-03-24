<?php
namespace ShlinkioTest\Shlink\Rest\Action;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Rest\Action\EditTagsAction;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;

class EditTagsActionTest extends TestCase
{
    /**
     * @var EditTagsAction
     */
    protected $action;
    /**
     * @var ObjectProphecy
     */
    private $shortUrlService;

    public function setUp()
    {
        $this->shortUrlService = $this->prophesize(ShortUrlService::class);
        $this->action = new EditTagsAction($this->shortUrlService->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function notProvidingTagsReturnsError()
    {
        $response = $this->action->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', 'abc123'),
            new Response()
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function anInvalidShortCodeReturnsNotFound()
    {
        $shortCode = 'abc123';
        $this->shortUrlService->setTagsByShortCode($shortCode, [])->willThrow(InvalidShortCodeException::class)
                                                                  ->shouldBeCalledTimes(1);

        $response = $this->action->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', 'abc123')
                                               ->withParsedBody(['tags' => []]),
            new Response()
        );
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function tagsListIsReturnedIfCorrectShortCodeIsProvided()
    {
        $shortCode = 'abc123';
        $this->shortUrlService->setTagsByShortCode($shortCode, [])->willReturn(new ShortUrl())
                                                                  ->shouldBeCalledTimes(1);

        $response = $this->action->__invoke(
            ServerRequestFactory::fromGlobals()->withAttribute('shortCode', 'abc123')
                ->withParsedBody(['tags' => []]),
            new Response()
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}
