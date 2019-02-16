<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Middleware\CloseDbConnectionMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class CloseDbConnectionMiddlewareTest extends TestCase
{
    /** @var CloseDbConnectionMiddleware */
    private $middleware;
    /** @var ObjectProphecy */
    private $handler;
    /** @var ObjectProphecy */
    private $em;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);

        $this->middleware = new CloseDbConnectionMiddleware($this->em->reveal());
    }

    /**
     * @test
     */
    public function connectionIsClosedWhenMiddlewareIsProcessed()
    {
        $req = new ServerRequest();
        $resp = new Response();

        $conn = $this->prophesize(Connection::class);
        $closeConn = $conn->close()->will(function () {
        });
        $getConn = $this->em->getConnection()->willReturn($conn->reveal());
        $clear = $this->em->clear()->will(function () {
        });
        $handle = $this->handler->handle($req)->willReturn($resp);

        $result = $this->middleware->process($req, $this->handler->reveal());

        $this->assertSame($result, $resp);
        $getConn->shouldHaveBeenCalledOnce();
        $closeConn->shouldHaveBeenCalledOnce();
        $clear->shouldHaveBeenCalledOnce();
        $handle->shouldHaveBeenCalledOnce();
    }
}
