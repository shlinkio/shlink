<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Rest\Action\HealthAction;

class HealthActionTest extends TestCase
{
    private HealthAction $action;
    private MockObject $conn;

    protected function setUp(): void
    {
        $this->conn = $this->createMock(Connection::class);
        $dbPlatform = $this->createMock(AbstractPlatform::class);
        $dbPlatform->method('getDummySelectSQL')->willReturn('');
        $this->conn->method('getDatabasePlatform')->willReturn($dbPlatform);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($this->conn);

        $this->action = new HealthAction($em, new AppOptions(version: '1.2.3'));
    }

    /** @test */
    public function passResponseIsReturnedWhenDummyQuerySucceeds(): void
    {
        $this->conn->expects($this->once())->method('executeQuery')->willReturn($this->createMock(Result::class));

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(new ServerRequest());
        $payload = $resp->getPayload();

        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('pass', $payload['status']);
        self::assertEquals('1.2.3', $payload['version']);
        self::assertEquals([
            'about' => 'https://shlink.io',
            'project' => 'https://github.com/shlinkio/shlink',
        ], $payload['links']);
        self::assertEquals('application/health+json', $resp->getHeaderLine('Content-type'));
    }

    /** @test */
    public function failResponseIsReturnedWhenDummyQueryThrowsException(): void
    {
        $this->conn->expects($this->once())->method('executeQuery')->willThrowException(new Exception());

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(new ServerRequest());
        $payload = $resp->getPayload();

        self::assertEquals(503, $resp->getStatusCode());
        self::assertEquals('fail', $payload['status']);
        self::assertEquals('1.2.3', $payload['version']);
        self::assertEquals([
            'about' => 'https://shlink.io',
            'project' => 'https://github.com/shlinkio/shlink',
        ], $payload['links']);
        self::assertEquals('application/health+json', $resp->getHeaderLine('Content-type'));
    }
}
