<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\Middleware\CloseDbConnectionMiddleware;
use Throwable;
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
    /** @var ObjectProphecy */
    private $conn;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->conn = $this->prophesize(Connection::class);
        $this->conn->close()->will(function () {
        });
        $this->em->getConnection()->willReturn($this->conn->reveal());
        $this->em->clear()->will(function () {
        });
        $this->em->isOpen()->willReturn(true);

        $this->middleware = new CloseDbConnectionMiddleware($this->em->reveal());
    }

    /** @test */
    public function connectionIsClosedWhenMiddlewareIsProcessed(): void
    {
        $req = new ServerRequest();
        $resp = new Response();
        $handle = $this->handler->handle($req)->willReturn($resp);

        $result = $this->middleware->process($req, $this->handler->reveal());

        $this->assertSame($result, $resp);
        $this->em->getConnection()->shouldHaveBeenCalledOnce();
        $this->conn->close()->shouldHaveBeenCalledOnce();
        $this->em->clear()->shouldHaveBeenCalledOnce();
        $handle->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function connectionIsClosedEvenIfExceptionIsThrownOnInnerMiddlewares(): void
    {
        $req = new ServerRequest();
        $expectedError = new RuntimeException();
        $this->handler->handle($req)->willThrow($expectedError)
                                    ->shouldBeCalledOnce();

        $this->em->getConnection()->shouldBeCalledOnce();
        $this->conn->close()->shouldBeCalledOnce();
        $this->em->clear()->shouldBeCalledOnce();
        $this->expectExceptionObject($expectedError);

        $this->middleware->process($req, $this->handler->reveal());
    }

    /**
     * @test
     * @dataProvider provideClosed
     */
    public function entityManagerIsReopenedAfterAnExceptionWhichClosedIt(bool $closed): void
    {
        $req = new ServerRequest();
        $expectedError = new RuntimeException();
        $this->handler->handle($req)->willThrow($expectedError)
                                    ->shouldBeCalledOnce();
        $this->em->closed = $closed;
        $this->em->isOpen()->willReturn(false);

        try {
            $this->middleware->process($req, $this->handler->reveal());
            $this->fail('Expected exception to be thrown but it did not.');
        } catch (Throwable $e) {
            $this->assertSame($expectedError, $e);
            $this->assertFalse($this->em->closed);
        }
    }

    public function provideClosed(): iterable
    {
        return [[true, false]];
    }
}
