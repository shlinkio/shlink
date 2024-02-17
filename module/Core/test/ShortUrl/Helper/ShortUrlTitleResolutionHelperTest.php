<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use Exception;
use GuzzleHttp\ClientInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelper;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;

class ShortUrlTitleResolutionHelperTest extends TestCase
{
    private MockObject & ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenResolvingTitlesIsDisabled(): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => 'http://foobar.com/12345/hello?foo=bar']);
        $this->httpClient->expects($this->never())->method('request');

        $result = $this->helper()->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenItAlreadyHasTitle(): void
    {
        $data = ShortUrlCreation::fromRawData([
            'longUrl' => 'http://foobar.com/12345/hello?foo=bar',
            'title' => 'foo',
        ]);
        $this->httpClient->expects($this->never())->method('request');

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenFetchingFails(): void
    {
        $data = ShortUrlCreation::fromRawData([
            'longUrl' => 'http://foobar.com/12345/hello?foo=bar',
        ]);
        $this->httpClient->expects($this->once())->method('request')->willThrowException(new Exception('Error'));

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenResponseIsNotHtml(): void
    {
        $data = ShortUrlCreation::fromRawData([
            'longUrl' => 'http://foobar.com/12345/hello?foo=bar',
        ]);
        $this->httpClient->expects($this->once())->method('request')->willReturn(new JsonResponse(['foo' => 'bar']));

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenTitleCannotBeResolvedFromResponse(): void
    {
        $data = ShortUrlCreation::fromRawData([
            'longUrl' => 'http://foobar.com/12345/hello?foo=bar',
        ]);
        $this->httpClient->expects($this->once())->method('request')->willReturn($this->respWithoutTitle());

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function titleIsUpdatedWhenItCanBeResolvedFromResponse(): void
    {
        $data = ShortUrlCreation::fromRawData([
            'longUrl' => 'http://foobar.com/12345/hello?foo=bar',
        ]);
        $this->httpClient->expects($this->once())->method('request')->willReturn($this->respWithTitle());

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertNotSame($data, $result);
        self::assertEquals('Resolved "title"', $result->title);
    }

    private function respWithoutTitle(): Response
    {
        $body = $this->createStreamWithContent('<body>No title</body>');
        return new Response($body, 200, ['Content-Type' => 'text/html']);
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

    private function helper(bool $autoResolveTitles = false): ShortUrlTitleResolutionHelper
    {
        return new ShortUrlTitleResolutionHelper(
            $this->httpClient,
            new UrlShortenerOptions(autoResolveTitles: $autoResolveTitles),
        );
    }
}
