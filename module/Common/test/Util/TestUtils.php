<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Util;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

class TestUtils
{
    private static $prophet;

    public static function createDelegateMock(ResponseInterface $response = null, RequestInterface $request = null)
    {
        $argument = $request ?: Argument::any();
        $delegate = static::getProphet()->prophesize(DelegateInterface::class);
        $delegate->process($argument)->willReturn($response ?: new Response());

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
