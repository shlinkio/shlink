<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortCode;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\ShortCode\DeleteShortCodeAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequestFactory;
use Zend\I18n\Translator\Translator;

class DeleteShortCodeActionTest extends TestCase
{
    /**
     * @var DeleteShortCodeAction
     */
    private $action;
    /**
     * @var ObjectProphecy
     */
    private $service;

    public function setUp()
    {
        $this->service = $this->prophesize(DeleteShortUrlServiceInterface::class);
        $this->action = new DeleteShortCodeAction($this->service->reveal(), Translator::factory([]));
    }

    /**
     * @test
     */
    public function emptyResponseIsReturnedIfProperlyDeleted()
    {
        $deleteByShortCode = $this->service->deleteByShortCode(Argument::any())->will(function () {
        });

        $resp = $this->action->handle(ServerRequestFactory::fromGlobals());

        $this->assertEquals(204, $resp->getStatusCode());
        $deleteByShortCode->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function returnsErrorResponseInCaseOfException(\Throwable $e, string $error, int $statusCode)
    {
        $deleteByShortCode = $this->service->deleteByShortCode(Argument::any())->willThrow($e);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(ServerRequestFactory::fromGlobals());
        $payload = $resp->getPayload();

        $this->assertEquals($statusCode, $resp->getStatusCode());
        $this->assertEquals($error, $payload['error']);
        $deleteByShortCode->shouldHaveBeenCalledTimes(1);
    }

    public function provideExceptions(): array
    {
        return [
            [new Exception\InvalidShortCodeException(), RestUtils::INVALID_SHORTCODE_ERROR, 404],
            [new Exception\DeleteShortUrlException(5), RestUtils::INVALID_SHORTCODE_DELETION_ERROR, 400],
        ];
    }
}
