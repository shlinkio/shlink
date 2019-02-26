<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\ErrorHandler;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Rest\ErrorHandler\JsonErrorResponseGenerator;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

use function array_map;
use function range;

class JsonErrorResponseGeneratorTest extends TestCase
{
    /** @var JsonErrorResponseGenerator */
    private $errorHandler;

    public function setUp(): void
    {
        $this->errorHandler = new JsonErrorResponseGenerator();
    }

    /** @test */
    public function noErrorStatusReturnsInternalServerError(): void
    {
        /** @var Response\JsonResponse $response */
        $response = $this->errorHandler->__invoke(null, new ServerRequest(), new Response());
        $payload = $response->getPayload();

        $this->assertInstanceOf(Response\JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', $payload['message']);
    }

    /**
     * @test
     * @dataProvider provideStatus
     */
    public function errorStatusReturnsThatStatus(int $status, string $message): void
    {
        /** @var Response\JsonResponse $response */
        $response = $this->errorHandler->__invoke(
            null,
            new ServerRequest(),
            (new Response())->withStatus($status, $message)
        );
        $payload = $response->getPayload();

        $this->assertInstanceOf(Response\JsonResponse::class, $response);
        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($message, $payload['message']);
    }

    public function provideStatus(): iterable
    {
        return array_map(function (int $status) {
            return [$status, 'Some message'];
        }, range(400, 500, 20));
    }
}
