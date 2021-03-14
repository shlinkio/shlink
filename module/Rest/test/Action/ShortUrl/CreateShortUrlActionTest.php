<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Rest\DataTransformerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Rest\Action\ShortUrl\CreateShortUrlAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class CreateShortUrlActionTest extends TestCase
{
    use ProphecyTrait;

    private CreateShortUrlAction $action;
    private ObjectProphecy $urlShortener;
    private ObjectProphecy $transformer;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->transformer = $this->prophesize(DataTransformerInterface::class);
        $this->transformer->transform(Argument::type(ShortUrl::class))->willReturn([]);

        $this->action = new CreateShortUrlAction($this->urlShortener->reveal(), $this->transformer->reveal());
    }

    /** @test */
    public function properShortcodeConversionReturnsData(): void
    {
        $apiKey = ApiKey::create();
        $shortUrl = ShortUrl::createEmpty();
        $expectedMeta = $body = [
            'longUrl' => 'http://www.domain.com/foo/bar',
            'validSince' => Chronos::now()->toAtomString(),
            'validUntil' => Chronos::now()->toAtomString(),
            'customSlug' => 'foo-bar-baz',
            'maxVisits' => 50,
            'findIfExists' => true,
            'domain' => 'my-domain.com',
        ];
        $expectedMeta['apiKey'] = $apiKey;

        $shorten = $this->urlShortener->shorten(ShortUrlMeta::fromRawData($expectedMeta))->willReturn($shortUrl);
        $transform = $this->transformer->transform($shortUrl)->willReturn(['shortUrl' => 'stringified_short_url']);

        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body)->withAttribute(ApiKey::class, $apiKey);

        /** @var JsonResponse $response */
        $response = $this->action->handle($request);
        $payload = $response->getPayload();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('stringified_short_url', $payload['shortUrl']);
        $shorten->shouldHaveBeenCalledOnce();
        $transform->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideInvalidDomains
     */
    public function anInvalidDomainReturnsError(string $domain): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $urlToShortCode = $this->urlShortener->shorten(Argument::cetera())->willReturn($shortUrl);

        $request = (new ServerRequest())->withParsedBody([
            'longUrl' => 'http://www.domain.com/foo/bar',
            'domain' => $domain,
        ])->withAttribute(ApiKey::class, ApiKey::create());

        $this->expectException(ValidationException::class);
        $urlToShortCode->shouldNotBeCalled();

        $this->action->handle($request);
    }

    public function provideInvalidDomains(): iterable
    {
        yield ['localhost:80000'];
        yield ['127.0.0.1'];
        yield ['???/&%$&'];
    }
}
