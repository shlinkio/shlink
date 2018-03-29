<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Util;

use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;

class TestUtils
{
    private static $prophet;

    public static function createReqHandlerMock(ResponseInterface $response = null, RequestInterface $request = null)
    {
        $argument = $request ?: Argument::any();
        $delegate = static::getProphet()->prophesize(RequestHandlerInterface::class);
        $delegate->handle($argument)->willReturn($response ?: new Response());

        return $delegate;
    }

    /**
     * @return Prophet
     */
    private static function getProphet()
    {
        if (static::$prophet === null) {
            static::$prophet = new Prophet();
        }

        return static::$prophet;
    }
}
