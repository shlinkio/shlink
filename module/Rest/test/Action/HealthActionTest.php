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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Rest\Action\HealthAction;

class HealthActionTest extends TestCase
{
    use ProphecyTrait;

    private HealthAction $action;
    private ObjectProphecy $conn;

    public function setUp(): void
    {
        $this->conn = $this->prophesize(Connection::class);
        $this->conn->executeQuery(Argument::cetera())->willReturn($this->prophesize(Result::class)->reveal());
        $dbPlatform = $this->prophesize(AbstractPlatform::class);
        $dbPlatform->getDummySelectSQL()->willReturn('');
        $this->conn->getDatabasePlatform()->willReturn($dbPlatform->reveal());

        $em = $this->prophesize(EntityManagerInterface::class);
        $em->getConnection()->willReturn($this->conn->reveal());

        $this->action = new HealthAction($em->reveal(), new AppOptions(['version' => '1.2.3']));
    }

    /** @test */
    public function passResponseIsReturnedWhenDummyQuerySucceeds(): void
    {
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
        $this->conn->executeQuery(Argument::cetera())->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function failResponseIsReturnedWhenDummyQueryThrowsException(): void
    {
        $executeQuery = $this->conn->executeQuery(Argument::cetera())->willThrow(Exception::class);

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
        $executeQuery->shouldHaveBeenCalledOnce();
    }
}
