<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Util\UrlValidator;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;

use function Functional\map;
use function range;

class UrlValidatorTest extends TestCase
{
    /** @var UrlValidator */
    private $urlValidator;
    /** @var ObjectProphecy */
    private $httpClient;

    public function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->urlValidator = new UrlValidator($this->httpClient->reveal());
    }

    /**
     * @test
     * @dataProvider provideAttemptThatThrows
     */
    public function exceptionIsThrownWhenUrlIsInvalid(int $attemptThatThrows): void
    {
        $callNum = 1;
        $e = new ClientException('', $this->prophesize(Request::class)->reveal());

        $request = $this->httpClient->request(Argument::cetera())->will(
            function () use ($e, $attemptThatThrows, &$callNum) {
                if ($callNum === $attemptThatThrows) {
                    throw $e;
                }

                $callNum++;
                return new Response('php://memory', 302, ['Location' => 'http://foo.com']);
            }
        );

        $request->shouldBeCalledTimes($attemptThatThrows);
        $this->expectException(InvalidUrlException::class);

        $this->urlValidator->validateUrl('http://foobar.com/12345/hello?foo=bar');
    }

    public function provideAttemptThatThrows(): iterable
    {
        return map(range(1, 15), function (int $attempt) {
            return [$attempt];
        });
    }

    /** @test */
    public function expectedUrlIsCalledWhenTryingToVerify(): void
    {
        $expectedUrl = 'http://foobar.com';

        $request = $this->httpClient->request(
            RequestMethodInterface::METHOD_GET,
            $expectedUrl,
            Argument::cetera()
        )->willReturn(new Response());

        $this->urlValidator->validateUrl($expectedUrl);

        $request->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function urlIsConsideredValidWhenTooManyRedirectsAreReturned(): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willReturn(
            new Response('php://memory', 302, ['Location' => 'http://foo.com'])
        );

        $this->urlValidator->validateUrl('http://foobar.com');

        $request->shouldHaveBeenCalledTimes(15);
    }
}
