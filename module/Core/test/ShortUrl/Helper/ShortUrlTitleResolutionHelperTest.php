<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use Exception;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelper;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;

class ShortUrlTitleResolutionHelperTest extends TestCase
{
    private const LONG_URL = 'http://foobar.com/12345/hello?foo=bar';

    private MockObject & ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenResolvingTitlesIsDisabled(): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $this->httpClient->expects($this->never())->method('request');

        $result = $this->helper()->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenItAlreadyHasTitle(): void
    {
        $data = ShortUrlCreation::fromRawData([
            'longUrl' => self::LONG_URL,
            'title' => 'foo',
        ]);
        $this->httpClient->expects($this->never())->method('request');

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenFetchingFails(): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $this->expectRequestToBeCalled()->willThrowException(new Exception('Error'));

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenResponseIsNotHtml(): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $this->expectRequestToBeCalled()->willReturn(new JsonResponse(['foo' => 'bar']));

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenTitleCannotBeResolvedFromResponse(): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $this->expectRequestToBeCalled()->willReturn($this->respWithoutTitle());

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    #[TestWith(['TEXT/html; charset=utf-8'], name: 'charset')]
    #[TestWith(['TEXT/html'], name: 'no charset')]
    public function titleIsUpdatedWhenItCanBeResolvedFromResponse(string $contentType): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $this->expectRequestToBeCalled()->willReturn($this->respWithTitle($contentType));

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertNotSame($data, $result);
        self::assertEquals('Resolved "title"', $result->title);
    }

    private function expectRequestToBeCalled(): InvocationMocker
    {
        return $this->httpClient->expects($this->once())->method('request')->with(
            RequestMethodInterface::METHOD_GET,
            self::LONG_URL,
            [
                RequestOptions::TIMEOUT => 3,
                RequestOptions::CONNECT_TIMEOUT => 3,
                RequestOptions::ALLOW_REDIRECTS => ['max' => ShortUrlTitleResolutionHelper::MAX_REDIRECTS],
                RequestOptions::IDN_CONVERSION => true,
                RequestOptions::HEADERS => ['User-Agent' => ShortUrlTitleResolutionHelper::CHROME_USER_AGENT],
                RequestOptions::STREAM => true,
            ],
        );
    }

    private function respWithoutTitle(): Response
    {
        $body = $this->createStreamWithContent('<body>No title</body>');
        return new Response($body, 200, ['Content-Type' => 'text/html']);
    }

    private function respWithTitle(string $contentType): Response
    {
        $body = $this->createStreamWithContent('<title data-foo="bar">  Resolved &quot;title&quot; </title>');
        return new Response($body, 200, ['Content-Type' => $contentType]);
    }

    private function createStreamWithContent(string $content): Stream
    {
        $body = new Stream('php://temp', 'wr');
        $body->write($content);
        $body->rewind();

        return $body;
    }

    private function helper(bool $autoResolveTitles = false): ShortUrlTitleResolutionHelper
    {
        return new ShortUrlTitleResolutionHelper(
            $this->httpClient,
            new UrlShortenerOptions(autoResolveTitles: $autoResolveTitles),
        );
    }
}
