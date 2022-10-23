<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Util\UrlValidator;

class UrlValidatorTest extends TestCase
{
    private MockObject $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
    }

    /** @test */
    public function exceptionIsThrownWhenUrlIsInvalid(): void
    {
        $this->httpClient->expects($this->once())->method('request')->willThrowException($this->clientException());
        $this->expectException(InvalidUrlException::class);

        $this->urlValidator()->validateUrl('http://foobar.com/12345/hello?foo=bar', true);
    }

    /** @test */
    public function expectedUrlIsCalledWhenTryingToVerify(): void
    {
        $expectedUrl = 'http://foobar.com';

        $this->httpClient->expects($this->once())->method('request')->with(
            RequestMethodInterface::METHOD_GET,
            $expectedUrl,
            $this->callback(function (array $options) {
                Assert::assertArrayHasKey(RequestOptions::ALLOW_REDIRECTS, $options);
                Assert::assertEquals(['max' => 15], $options[RequestOptions::ALLOW_REDIRECTS]);
                Assert::assertArrayHasKey(RequestOptions::IDN_CONVERSION, $options);
                Assert::assertTrue($options[RequestOptions::IDN_CONVERSION]);
                Assert::assertArrayHasKey(RequestOptions::HEADERS, $options);
                Assert::assertArrayHasKey('User-Agent', $options[RequestOptions::HEADERS]);

                return true;
            }),
        )->willReturn(new Response());

        $this->urlValidator()->validateUrl($expectedUrl, true);
    }

    /** @test */
    public function noCheckIsPerformedWhenUrlValidationIsDisabled(): void
    {
        $this->httpClient->expects($this->never())->method('request');
        $this->urlValidator()->validateUrl('', false);
    }

    /** @test */
    public function validateUrlWithTitleReturnsNullWhenRequestFailsAndValidationIsDisabled(): void
    {
        $this->httpClient->expects($this->once())->method('request')->willThrowException($this->clientException());

        $result = $this->urlValidator(true)->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', false);

        self::assertNull($result);
    }

    /** @test */
    public function validateUrlWithTitleReturnsNullWhenAutoResolutionIsDisabled(): void
    {
        $this->httpClient->expects($this->never())->method('request');

        $result = $this->urlValidator()->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', false);

        self::assertNull($result);
    }

    /** @test */
    public function validateUrlWithTitleReturnsNullWhenAutoResolutionIsDisabledAndValidationIsEnabled(): void
    {
        $this->httpClient->expects($this->once())->method('request')->with(
            RequestMethodInterface::METHOD_HEAD,
            $this->anything(),
            $this->anything(),
        )->willReturn($this->respWithTitle());

        $result = $this->urlValidator()->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', true);

        self::assertNull($result);
    }

    /** @test */
    public function validateUrlWithTitleResolvesTitleWhenAutoResolutionIsEnabled(): void
    {
        $this->httpClient->expects($this->once())->method('request')->with(
            RequestMethodInterface::METHOD_GET,
            $this->anything(),
            $this->anything(),
        )->willReturn($this->respWithTitle());

        $result = $this->urlValidator(true)->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', true);

        self::assertEquals('Resolved "title"', $result);
    }

    /** @test */
    public function validateUrlWithTitleReturnsNullWhenAutoResolutionIsEnabledAndReturnedContentTypeIsInvalid(): void
    {
        $this->httpClient->expects($this->once())->method('request')->with(
            RequestMethodInterface::METHOD_GET,
            $this->anything(),
            $this->anything(),
        )->willReturn(new Response('php://memory', 200, ['Content-Type' => 'application/octet-stream']));

        $result = $this->urlValidator(true)->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', true);

        self::assertNull($result);
    }

    /** @test */
    public function validateUrlWithTitleReturnsNullWhenAutoResolutionIsEnabledAndBodyDoesNotContainTitle(): void
    {
        $this->httpClient->expects($this->once())->method('request')->with(
            RequestMethodInterface::METHOD_GET,
            $this->anything(),
            $this->anything(),
        )->willReturn(
            new Response($this->createStreamWithContent('<body>No title</body>'), 200, ['Content-Type' => 'text/html']),
        );

        $result = $this->urlValidator(true)->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', true);

        self::assertNull($result);
    }

    private function respWithTitle(): Response
    {
        $body = $this->createStreamWithContent('<title data-foo="bar">  Resolved &quot;title&quot; </title>');
        return new Response($body, 200, ['Content-Type' => 'TEXT/html; charset=utf-8']);
    }

    private function createStreamWithContent(string $content): Stream
    {
        $body = new Stream('php://temp', 'wr');
        $body->write($content);
        $body->rewind();

        return $body;
    }

    private function clientException(): ClientException
    {
        return new ClientException(
            '',
            new Request(RequestMethodInterface::METHOD_GET, ''),
            new Response(),
        );
    }

    public function urlValidator(bool $autoResolveTitles = false): UrlValidator
    {
        return new UrlValidator($this->httpClient, new UrlShortenerOptions(autoResolveTitles: $autoResolveTitles));
    }
}
