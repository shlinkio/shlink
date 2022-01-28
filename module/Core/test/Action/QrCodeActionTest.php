<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Common\Response\QrCodeResponse;
use Shlinkio\Shlink\Core\Action\QrCodeAction;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Options\QrCodeOptions;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;

use function getimagesizefromstring;
use function imagecolorat;
use function imagecreatefromstring;

class QrCodeActionTest extends TestCase
{
    use ProphecyTrait;

    private const WHITE = 0xFFFFFF;
    private const BLACK = 0x0;

    private QrCodeAction $action;
    private ObjectProphecy $urlResolver;
    private QrCodeOptions $options;

    public function setUp(): void
    {
        $router = $this->prophesize(RouterInterface::class);
        $router->generateUri(Argument::cetera())->willReturn('/foo/bar');

        $this->urlResolver = $this->prophesize(ShortUrlResolverInterface::class);
        $this->options = new QrCodeOptions();

        $this->action = new QrCodeAction(
            $this->urlResolver->reveal(),
            new ShortUrlStringifier(['domain' => 'doma.in']),
            new NullLogger(),
            $this->options,
        );
    }

    /** @test */
    public function aNotFoundShortCodeWillDelegateIntoNextMiddleware(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))
            ->willThrow(ShortUrlNotFoundException::class)
            ->shouldBeCalledOnce();
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $process = $delegate->handle(Argument::any())->willReturn(new Response());

        $this->action->process((new ServerRequest())->withAttribute('shortCode', $shortCode), $delegate->reveal());

        $process->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function aCorrectRequestReturnsTheQrCodeResponse(): void
    {
        $shortCode = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($shortCode, ''))
            ->willReturn(ShortUrl::createEmpty())
            ->shouldBeCalledOnce();
        $delegate = $this->prophesize(RequestHandlerInterface::class);

        $resp = $this->action->process(
            (new ServerRequest())->withAttribute('shortCode', $shortCode),
            $delegate->reveal(),
        );

        self::assertInstanceOf(QrCodeResponse::class, $resp);
        self::assertEquals(200, $resp->getStatusCode());
        $delegate->handle(Argument::any())->shouldHaveBeenCalledTimes(0);
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
        $this->options->setFromArray(['format' => $defaultFormat]);
        $code = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($code, ''))->willReturn(
            ShortUrl::createEmpty(),
        );
        $delegate = $this->prophesize(RequestHandlerInterface::class);
        $req = (new ServerRequest())->withAttribute('shortCode', $code)->withQueryParams($query);

        $resp = $this->action->process($req, $delegate->reveal());

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
        array $defaults,
        ServerRequestInterface $req,
        int $expectedSize,
    ): void {
        $this->options->setFromArray($defaults);
        $code = 'abc123';
        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($code, ''))->willReturn(
            ShortUrl::createEmpty(),
        );
        $delegate = $this->prophesize(RequestHandlerInterface::class);

        $resp = $this->action->process($req->withAttribute('shortCode', $code), $delegate->reveal());
        [$size] = getimagesizefromstring($resp->getBody()->__toString());

        self::assertEquals($expectedSize, $size);
    }

    public function provideRequestsWithSize(): iterable
    {
        yield 'different margin and size defaults' => [
            ['size' => 660, 'margin' => 40],
            ServerRequestFactory::fromGlobals(),
            740,
        ];
        yield 'no size' => [[], ServerRequestFactory::fromGlobals(), 300];
        yield 'no size, different default' => [['size' => 500], ServerRequestFactory::fromGlobals(), 500];
        yield 'size in query' => [[], ServerRequestFactory::fromGlobals()->withQueryParams(['size' => '123']), 123];
        yield 'size in query, default margin' => [
            ['margin' => 25],
            ServerRequestFactory::fromGlobals()->withQueryParams(['size' => '123']),
            173,
        ];
        yield 'margin' => [[], ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '35']), 370];
        yield 'margin and different default' => [
            ['size' => 400],
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '35']),
            470,
        ];
        yield 'margin and size' => [
            [],
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '100', 'size' => '200']),
            400,
        ];
        yield 'negative margin' => [[], ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '-50']), 300];
        yield 'negative margin, default margin' => [
            ['margin' => 10],
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '-50']),
            300,
        ];
        yield 'non-numeric margin' => [
            [],
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => 'foo']),
            300,
        ];
        yield 'negative margin and size' => [
            [],
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '-1', 'size' => '150']),
            150,
        ];
        yield 'negative margin and size, default margin' => [
            ['margin' => 5],
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => '-1', 'size' => '150']),
            150,
        ];
        yield 'non-numeric margin and size' => [
            [],
            ServerRequestFactory::fromGlobals()->withQueryParams(['margin' => 'foo', 'size' => '538']),
            538,
        ];
    }

    /**
     * @test
     * @dataProvider provideRoundBlockSize
     */
    public function imageCanRemoveExtraMarginWhenBlockRoundIsDisabled(
        array $defaults,
        ?string $roundBlockSize,
        int $expectedColor,
    ): void {
        $this->options->setFromArray($defaults);
        $code = 'abc123';
        $req = ServerRequestFactory::fromGlobals()
            ->withQueryParams(['size' => 250, 'roundBlockSize' => $roundBlockSize])
            ->withAttribute('shortCode', $code);

        $this->urlResolver->resolveEnabledShortUrl(new ShortUrlIdentifier($code, ''))->willReturn(
            ShortUrl::withLongUrl('https://shlink.io'),
        );
        $delegate = $this->prophesize(RequestHandlerInterface::class);

        $resp = $this->action->process($req, $delegate->reveal());
        $image = imagecreatefromstring($resp->getBody()->__toString());
        $color = imagecolorat($image, 1, 1);

        self::assertEquals($color, $expectedColor);
    }

    public function provideRoundBlockSize(): iterable
    {
        yield 'no round block param' => [[], null, self::WHITE];
        yield 'no round block param, but disabled by default' => [['round_block_size' => false], null, self::BLACK];
        yield 'round block: "true"' => [[], 'true', self::WHITE];
        yield 'round block: "true", but disabled by default' => [['round_block_size' => false], 'true', self::WHITE];
        yield 'round block: "false"' => [[], 'false', self::BLACK];
        yield 'round block: "false", but enabled by default' => [['round_block_size' => true], 'false', self::BLACK];
    }
}
