<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Action\QrCodeAction;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Options\QrCodeOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;

use function getimagesizefromstring;
use function imagecolorat;
use function imagecreatefromstring;

class QrCodeActionTest extends TestCase
{
    private const WHITE = 0xFFFFFF;
    private const BLACK = 0x0;

    private MockObject $urlResolver;

    protected function setUp(): void
    {
        $this->urlResolver = $this->createMock(ShortUrlResolverInterface::class);
    }

    /** @test */
    public function aNotFoundShortCodeWillDelegateIntoNextMiddleware(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->expects($this->once())->method('resolveEnabledShortUrl')->with(
            $this->equalTo(ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, '')),
        )->willThrowException(ShortUrlNotFoundException::fromNotFound(ShortUrlIdentifier::fromShortCodeAndDomain('')));
        $delegate = $this->createMock(RequestHandlerInterface::class);
        $delegate->expects($this->once())->method('handle')->withAnyParameters()->willReturn(new Response());

        $this->action()->process((new ServerRequest())->withAttribute('shortCode', $shortCode), $delegate);
    }

    /** @test */
    public function aCorrectRequestReturnsTheQrCodeResponse(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->expects($this->once())->method('resolveEnabledShortUrl')->with(
            $this->equalTo(ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, '')),
        )->willReturn(ShortUrl::createEmpty());
        $delegate = $this->createMock(RequestHandlerInterface::class);
        $delegate->expects($this->never())->method('handle');

        $resp = $this->action()->process((new ServerRequest())->withAttribute('shortCode', $shortCode), $delegate);

        self::assertInstanceOf(QrCodeResponse::class, $resp);
        self::assertEquals(200, $resp->getStatusCode());
    }

    /**
     * @test
     * @dataProvider provideQueries
     */
    public function imageIsReturnedWithExpectedContentTypeBasedOnProvidedFormat(
        string $defaultFormat,
        array $query,
        string $expectedContentType,
    ): void {
        $code = 'abc123';
        $this->urlResolver->method('resolveEnabledShortUrl')->with(
            $this->equalTo(ShortUrlIdentifier::fromShortCodeAndDomain($code, '')),
        )->willReturn(ShortUrl::createEmpty());
        $delegate = $this->createMock(RequestHandlerInterface::class);
        $req = (new ServerRequest())->withAttribute('shortCode', $code)->withQueryParams($query);

        $resp = $this->action(new QrCodeOptions(format: $defaultFormat))->process($req, $delegate);

        self::assertEquals($expectedContentType, $resp->getHeaderLine('Content-Type'));
    }

    public function provideQueries(): iterable
    {
        yield 'no format, png default' => ['png', [], 'image/png'];
        yield 'no format, svg default' => ['svg', [], 'image/svg+xml'];
        yield 'png format, png default' => ['png', ['format' => 'png'], 'image/png'];
        yield 'png format, svg default' => ['svg', ['format' => 'png'], 'image/png'];
        yield 'svg format, png default' => ['png', ['format' => 'svg'], 'image/svg+xml'];
        yield 'svg format, svg default' => ['svg', ['format' => 'svg'], 'image/svg+xml'];
        yield 'unsupported format, png default' => ['png', ['format' => 'jpg'], 'image/png'];
        yield 'unsupported format, svg default' => ['svg', ['format' => 'jpg'], 'image/svg+xml'];
    }

    /**
     * @test
     * @dataProvider provideRequestsWithSize
     */
    public function imageIsReturnedWithExpectedSize(
        QrCodeOptions $defaultOptions,
        ServerRequestInterface $req,
        int $expectedSize,
    ): void {
        $code = 'abc123';
        $this->urlResolver->method('resolveEnabledShortUrl')->with(
            $this->equalTo(ShortUrlIdentifier::fromShortCodeAndDomain($code, '')),
        )->willReturn(ShortUrl::createEmpty());
        $delegate = $this->createMock(RequestHandlerInterface::class);

        $resp = $this->action($defaultOptions)->process($req->withAttribute('shortCode', $code), $delegate);
        [$size] = getimagesizefromstring($resp->getBody()->__toString());

        self::assertEquals($expectedSize, $size);
    }

    public function provideRequestsWithSize(): iterable
    {
        yield 'different margin and size defaults' => [
            new QrCodeOptions(size: 660, margin: 40),
            ServerRequestFactory::fromGlobals(),
            740,
        ];
        yield 'no size' => [new QrCodeOptions(), ServerRequestFactory::fromGlobals(), 300];
        yield 'no size, different default' => [new QrCodeOptions(size: 500), ServerRequestFactory::fromGlobals(), 500];
        yield 'size in query' => [
            new QrCodeOptions(),
            ServerRequestFactory::fromGlobals()->withQueryParams(['size' => '123']),
            123,
        ];
        yield 'size in query, default margin' => [
            new QrCodeOptions(margin: 25),
            ServerRequestFactory::fromGlobals()->withQueryParams(['size' => '123']),
            173,
        ];
        yield 'margin' => [
            new QrCodeOptions(),
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '35']),
            370,
        ];
        yield 'margin and different default' => [
            new QrCodeOptions(size: 400),
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '35']),
            470,
        ];
        yield 'margin and size' => [
            new QrCodeOptions(),
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '100', 'size' => '200']),
            400,
        ];
        yield 'negative margin' => [
            new QrCodeOptions(),
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '-50']),
            300,
        ];
        yield 'negative margin, default margin' => [
            new QrCodeOptions(margin: 10),
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '-50']),
            300,
        ];
        yield 'non-numeric margin' => [
            new QrCodeOptions(),
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => 'foo']),
            300,
        ];
        yield 'negative margin and size' => [
            new QrCodeOptions(),
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '-1', 'size' => '150']),
            150,
        ];
        yield 'negative margin and size, default margin' => [
            new QrCodeOptions(margin: 5),
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '-1', 'size' => '150']),
            150,
        ];
        yield 'non-numeric margin and size' => [
            new QrCodeOptions(),
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => 'foo', 'size' => '538']),
            538,
        ];
    }

    /**
     * @test
     * @dataProvider provideRoundBlockSize
     */
    public function imageCanRemoveExtraMarginWhenBlockRoundIsDisabled(
        QrCodeOptions $defaultOptions,
        ?string $roundBlockSize,
        int $expectedColor,
    ): void {
        $code = 'abc123';
        $req = ServerRequestFactory::fromGlobals()
            ->withQueryParams(['size' => 250, 'roundBlockSize' => $roundBlockSize])
            ->withAttribute('shortCode', $code);

        $this->urlResolver->method('resolveEnabledShortUrl')->with(
            $this->equalTo(ShortUrlIdentifier::fromShortCodeAndDomain($code, '')),
        )->willReturn(ShortUrl::withLongUrl('https://shlink.io'));
        $delegate = $this->createMock(RequestHandlerInterface::class);

        $resp = $this->action($defaultOptions)->process($req, $delegate);
        $image = imagecreatefromstring($resp->getBody()->__toString());
        $color = imagecolorat($image, 1, 1);

        self::assertEquals($color, $expectedColor);
    }

    public function provideRoundBlockSize(): iterable
    {
        yield 'no round block param' => [new QrCodeOptions(), null, self::WHITE];
        yield 'no round block param, but disabled by default' => [
            new QrCodeOptions(roundBlockSize: false),
            null,
            self::BLACK,
        ];
        yield 'round block: "true"' => [new QrCodeOptions(), 'true', self::WHITE];
        yield 'round block: "true", but disabled by default' => [
            new QrCodeOptions(roundBlockSize: false),
            'true',
            self::WHITE,
        ];
        yield 'round block: "false"' => [new QrCodeOptions(), 'false', self::BLACK];
        yield 'round block: "false", but enabled by default' => [
            new QrCodeOptions(roundBlockSize: true),
            'false',
            self::BLACK,
        ];
    }

    public function action(?QrCodeOptions $options = null): QrCodeAction
    {
        return new QrCodeAction(
            $this->urlResolver,
            new ShortUrlStringifier(['domain' => 'doma.in']),
            new NullLogger(),
            $options ?? new QrCodeOptions(),
        );
    }
}
