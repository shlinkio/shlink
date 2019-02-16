<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Rest\Action\ShortUrl\DeleteShortUrlAction;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Throwable;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class DeleteShortUrlActionTest extends TestCase
{
    /** @var DeleteShortUrlAction */
    private $action;
    /** @var ObjectProphecy */
    private $service;

    public function setUp(): void
    {
        $this->service = $this->prophesize(DeleteShortUrlServiceInterface::class);
        $this->action = new DeleteShortUrlAction($this->service->reveal());
    }

    /**
     * @test
     */
    public function emptyResponseIsReturnedIfProperlyDeleted()
    {
        $deleteByShortCode = $this->service->deleteByShortCode(Argument::any())->will(function () {
        });

        $resp = $this->action->handle(new ServerRequest());

        $this->assertEquals(204, $resp->getStatusCode());
        $deleteByShortCode->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function returnsErrorResponseInCaseOfException(Throwable $e, string $error, int $statusCode)
    {
        $deleteByShortCode = $this->service->deleteByShortCode(Argument::any())->willThrow($e);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(new ServerRequest());
        $payload = $resp->getPayload();

        $this->assertEquals($statusCode, $resp->getStatusCode());
        $this->assertEquals($error, $payload['error']);
        $deleteByShortCode->shouldHaveBeenCalledOnce();
    }

    public function provideExceptions(): array
    {
        return [
            [new Exception\InvalidShortCodeException(), RestUtils::INVALID_SHORTCODE_ERROR, 404],
            [new Exception\DeleteShortUrlException(5), RestUtils::INVALID_SHORTCODE_DELETION_ERROR, 400],
        ];
    }
}
