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

    /** @test */
    public function exceptionIsThrownWhenUrlIsInvalid(): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willThrow(
            new ClientException('', $this->prophesize(Request::class)->reveal())
        );

        $this->expectException(InvalidUrlException::class);
        $request->shouldBeCalledOnce();

        $this->urlValidator->validateUrl('http://foobar.com/12345/hello?foo=bar');
    }

    /**
     * @test
     * @dataProvider provideUrls
     */
    public function expectedUrlIsCalledInOrderToVerifyProvidedUrl(string $providedUrl, string $expectedUrl): void
    {
        $request = $this->httpClient->request(
            RequestMethodInterface::METHOD_GET,
            $expectedUrl,
            Argument::cetera()
        )->will(function () {
        });

        $this->urlValidator->validateUrl($providedUrl);

        $request->shouldHaveBeenCalledOnce();
    }

    public function provideUrls(): iterable
    {
        yield 'regular domain' => ['http://foobar.com', 'http://foobar.com'];
        yield 'IDN' => ['https://cédric.laubacher.io/', 'https://xn--cdric-bsa.laubacher.io/'];
    }
}
