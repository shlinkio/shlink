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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\InvocationStubber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelper;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;

class ShortUrlTitleResolutionHelperTest extends TestCase
{
    private const string LONG_URL = 'https://foobar.com/12345/hello?foo=bar';

    private MockObject & ClientInterface $httpClient;
    private MockObject & LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenResolvingTitlesIsDisabled(): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $this->httpClient->expects($this->never())->method('request');
        $this->logger->expects($this->never())->method('warning');

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
        $this->logger->expects($this->never())->method('warning');

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenFetchingFails(): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $this->expectRequestToBeCalled()->willThrowException(new Exception('Error'));
        $this->logger->expects($this->never())->method('warning');

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenResponseIsNotHtml(): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $this->expectRequestToBeCalled()->willReturn(new JsonResponse(['foo' => 'bar']));
        $this->logger->expects($this->never())->method('warning');

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    public function dataIsReturnedAsIsWhenTitleCannotBeResolvedFromResponse(): void
    {
        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $this->expectRequestToBeCalled()->willReturn($this->respWithoutTitle());
        $this->logger->expects($this->never())->method('warning');

        $result = $this->helper(autoResolveTitles: true)->processTitle($data);

        self::assertSame($data, $result);
    }

    #[Test]
    #[TestWith(['TEXT/html; charset=utf-8', false], 'mbstring-supported charset')]
    #[TestWith(['TEXT/html; charset=Windows-1255', true], 'mbstring-unsupported charset')]
    public function titleIsUpdatedWhenItCanBeResolvedFromResponse(string $contentType, bool $expectsWarning): void
    {
        $this->expectRequestToBeCalled()->willReturn($this->respWithTitle($contentType));
        if ($expectsWarning) {
            $this->logger->expects($this->once())->method('warning')->with(
                'It was impossible to encode page title in UTF-8 with mb_convert_encoding. {e}',
                $this->isArray(),
            );
        } else {
            $this->logger->expects($this->never())->method('warning');
        }

        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $result = $this->helper(autoResolveTitles: true, iconvEnabled: true)->processTitle($data);

        self::assertNotSame($data, $result);
        self::assertEquals('Resolved "title"', $result->title);
    }

    #[Test, AllowMockObjectsWithoutExpectations]
    public function resolvedTitleIsIgnoredWhenCharsetCannotBeResolved(): void
    {
        $this->expectRequestToBeCalled()->willReturn($this->respWithTitle('text/html'));

        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $result = $this->helper(autoResolveTitles: true, iconvEnabled: true)->processTitle($data);

        self::assertSame($data, $result);
        self::assertNull($result->title);
    }

    #[Test, AllowMockObjectsWithoutExpectations]
    #[TestWith(['<meta charset="utf-8">'])]
    #[TestWith(['<meta charset="utf-8" />'])]
    #[TestWith(['<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'])]
    #[TestWith(['<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'])]
    public function pageCharsetCanBeReadFromMeta(string $extraContent): void
    {
        $this->expectRequestToBeCalled()->willReturn($this->respWithTitle(
            contentType: 'text/html',
            extraContent: $extraContent,
        ));

        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $result = $this->helper(autoResolveTitles: true, iconvEnabled: true)->processTitle($data);

        self::assertNotSame($data, $result);
        self::assertEquals('Resolved "title"', $result->title);
    }

    #[Test]
    #[TestWith([
        'contentType' => 'text/html; charset=Windows-1255',
        'iconvEnabled' => false,
        'expectedSecondMessage' => 'Missing iconv extension. Skipping title encoding',
    ])]
    #[TestWith([
        'contentType' => 'text/html; charset=foo',
        'iconvEnabled' => true,
        'expectedSecondMessage' => 'It was impossible to encode page title in UTF-8 with iconv. {e}',
    ])]
    public function warningsLoggedWhenTitleCannotBeEncodedToUtf8(
        string $contentType,
        bool $iconvEnabled,
        string $expectedSecondMessage,
    ): void {
        $this->expectRequestToBeCalled()->willReturn($this->respWithTitle($contentType));
        $callCount = 0;
        $this->logger->expects($this->exactly(2))->method('warning')->with($this->callback(
            function (string $message) use (&$callCount, $expectedSecondMessage): bool {
                $callCount++;
                if ($callCount === 1) {
                    return $message === 'It was impossible to encode page title in UTF-8 with mb_convert_encoding. {e}';
                }

                return $message === $expectedSecondMessage;
            },
        ));

        $data = ShortUrlCreation::fromRawData(['longUrl' => self::LONG_URL]);
        $result = $this->helper(autoResolveTitles: true, iconvEnabled: $iconvEnabled)->processTitle($data);

        self::assertNotSame($data, $result);
        self::assertEquals('Resolved "title"', $result->title);
    }

    private function expectRequestToBeCalled(): InvocationStubber
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

    private function respWithTitle(string $contentType, string|null $extraContent = null): Response
    {
        $content = '<title data-foo="bar">  Resolved &quot;title&quot; </title>';
        if ($extraContent !== null) {
            $content .= $extraContent;
        }

        $body = $this->createStreamWithContent($content);
        return new Response($body, 200, ['Content-Type' => $contentType]);
    }

    private function createStreamWithContent(string $content): Stream
    {
        $body = new Stream('php://temp', 'wr');
        $body->write($content);
        $body->rewind();

        return $body;
    }

    private function helper(bool $autoResolveTitles = false, bool $iconvEnabled = false): ShortUrlTitleResolutionHelper
    {
        return new ShortUrlTitleResolutionHelper(
            $this->httpClient,
            new UrlShortenerOptions(autoResolveTitles: $autoResolveTitles),
            $this->logger,
            fn () => $iconvEnabled,
        );
    }
}
