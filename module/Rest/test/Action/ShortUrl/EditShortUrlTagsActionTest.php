<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Rest\Action\ShortUrl\EditShortUrlTagsAction;
use Zend\Diactoros\ServerRequest;

class EditShortUrlTagsActionTest extends TestCase
{
    /** @var EditShortUrlTagsAction */
    private $action;
    /** @var ObjectProphecy */
    private $shortUrlService;

    public function setUp(): void
    {
        $this->shortUrlService = $this->prophesize(ShortUrlService::class);
        $this->action = new EditShortUrlTagsAction($this->shortUrlService->reveal());
    }

    /** @test */
    public function notProvidingTagsReturnsError()
    {
        $response = $this->action->handle((new ServerRequest())->withAttribute('shortCode', 'abc123'));
        $this->assertEquals(400, $response->getStatusCode());
    }

    /** @test */
    public function anInvalidShortCodeReturnsNotFound()
    {
        $shortCode = 'abc123';
        $this->shortUrlService->setTagsByShortCode($shortCode, [])->willThrow(InvalidShortCodeException::class)
                                                                  ->shouldBeCalledOnce();

        $response = $this->action->handle(
            (new ServerRequest())->withAttribute('shortCode', 'abc123')
                                 ->withParsedBody(['tags' => []])
        );
        $this->assertEquals(404, $response->getStatusCode());
    }

    /** @test */
    public function tagsListIsReturnedIfCorrectShortCodeIsProvided()
    {
        $shortCode = 'abc123';
        $this->shortUrlService->setTagsByShortCode($shortCode, [])->willReturn(new ShortUrl(''))
                                                                  ->shouldBeCalledOnce();

        $response = $this->action->handle(
            (new ServerRequest())->withAttribute('shortCode', 'abc123')
                                 ->withParsedBody(['tags' => []])
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}
