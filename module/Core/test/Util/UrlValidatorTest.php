<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Util\UrlValidator;

class UrlValidatorTest extends TestCase
{
    use ProphecyTrait;

    private UrlValidator $urlValidator;
    private ObjectProphecy $httpClient;
    private UrlShortenerOptions $options;

    public function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->options = new UrlShortenerOptions(['validate_url' => true]);
        $this->urlValidator = new UrlValidator($this->httpClient->reveal(), $this->options);
    }

    /** @test */
    public function exceptionIsThrownWhenUrlIsInvalid(): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willThrow(ClientException::class);

        $request->shouldBeCalledOnce();
        $this->expectException(InvalidUrlException::class);

        $this->urlValidator->validateUrl('http://foobar.com/12345/hello?foo=bar', true);
    }

    /** @test */
    public function expectedUrlIsCalledWhenTryingToVerify(): void
    {
        $expectedUrl = 'http://foobar.com';

        $request = $this->httpClient->request(
            RequestMethodInterface::METHOD_GET,
            $expectedUrl,
            Argument::that(function (array $options) {
                Assert::assertArrayHasKey(RequestOptions::ALLOW_REDIRECTS, $options);
                Assert::assertEquals(['max' => 15], $options[RequestOptions::ALLOW_REDIRECTS]);
                Assert::assertArrayHasKey(RequestOptions::IDN_CONVERSION, $options);
                Assert::assertTrue($options[RequestOptions::IDN_CONVERSION]);
                Assert::assertArrayHasKey(RequestOptions::HEADERS, $options);
                Assert::assertArrayHasKey('User-Agent', $options[RequestOptions::HEADERS]);

                return true;
            }),
        )->willReturn(new Response());

        $this->urlValidator->validateUrl($expectedUrl, true);

        $request->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function noCheckIsPerformedWhenUrlValidationIsDisabled(): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willReturn(new Response());

        $this->urlValidator->validateUrl('', false);

        $request->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function validateUrlWithTitleReturnsNullWhenRequestFailsAndValidationIsDisabled(): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willThrow(ClientException::class);
        $this->options->autoResolveTitles = true;

        $result = $this->urlValidator->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', false);

        self::assertNull($result);
        $request->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function validateUrlWithTitleReturnsNullWhenAutoResolutionIsDisabled(): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willReturn($this->respWithTitle());
        $this->options->autoResolveTitles = false;

        $result = $this->urlValidator->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', false);

        self::assertNull($result);
        $request->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function validateUrlWithTitleReturnsNullWhenAutoResolutionIsDisabledAndValidationIsEnabled(): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willReturn($this->respWithTitle());
        $this->options->autoResolveTitles = false;

        $result = $this->urlValidator->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', true);

        self::assertNull($result);
        $request->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function validateUrlWithTitleResolvesTitleWhenAutoResolutionIsEnabled(): void
    {
        $request = $this->httpClient->request(Argument::cetera())->willReturn($this->respWithTitle());
        $this->options->autoResolveTitles = true;

        $result = $this->urlValidator->validateUrlWithTitle('http://foobar.com/12345/hello?foo=bar', true);

        self::assertEquals('Resolved title', $result);
        $request->shouldHaveBeenCalledOnce();
    }

    private function respWithTitle(): Response
    {
        $body = new Stream('php://temp', 'wr');
        $body->write('<title>  Resolved title</title>');

        return new Response($body);
    }
}
