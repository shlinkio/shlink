<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Validation\DomainRedirectsInputFilter;
use Shlinkio\Shlink\Core\Entity\Domain;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Rest\Action\Domain\DomainRedirectsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function array_key_exists;

class DomainRedirectsActionTest extends TestCase
{
    use ProphecyTrait;

    private DomainRedirectsAction $action;
    private ObjectProphecy $domainService;

    protected function setUp(): void
    {
        $this->domainService = $this->prophesize(DomainServiceInterface::class);
        $this->action = new DomainRedirectsAction($this->domainService->reveal());
    }

    /**
     * @test
     * @dataProvider provideInvalidBodies
     */
    public function invalidDataThrowsException(array $body): void
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body);

        $this->expectException(ValidationException::class);
        $this->domainService->getOrCreate(Argument::cetera())->shouldNotBeCalled();
        $this->domainService->configureNotFoundRedirects(Argument::cetera())->shouldNotBeCalled();

        $this->action->handle($request);
    }

    public function provideInvalidBodies(): iterable
    {
        yield 'no domain' => [[]];
        yield 'empty domain' => [['domain' => '']];
        yield 'invalid domain' => [['domain' => '192.168.1.20']];
    }

    /**
     * @test
     * @dataProvider provideDomainsAndRedirects
     */
    public function domainIsFetchedAndUsedToGetItConfigured(
        Domain $domain,
        array $redirects,
        array $expectedResult,
    ): void {
        $authority = 'doma.in';
        $redirects['domain'] = $authority;
        $apiKey = ApiKey::create();
        $request = ServerRequestFactory::fromGlobals()->withParsedBody($redirects)
                                                      ->withAttribute(ApiKey::class, $apiKey);

        $getOrCreate = $this->domainService->getOrCreate($authority)->willReturn($domain);
        $configureNotFoundRedirects = $this->domainService->configureNotFoundRedirects(
            $authority,
            NotFoundRedirects::withRedirects(
                array_key_exists(DomainRedirectsInputFilter::BASE_URL_REDIRECT, $redirects)
                    ? $redirects[DomainRedirectsInputFilter::BASE_URL_REDIRECT]
                    : $domain?->baseUrlRedirect(),
                array_key_exists(DomainRedirectsInputFilter::REGULAR_404_REDIRECT, $redirects)
                    ? $redirects[DomainRedirectsInputFilter::REGULAR_404_REDIRECT]
                    : $domain?->regular404Redirect(),
                array_key_exists(DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT, $redirects)
                    ? $redirects[DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT]
                    : $domain?->invalidShortUrlRedirect(),
            ),
            $apiKey,
        );

        /** @var JsonResponse $response */
        $response = $this->action->handle($request);
        /** @var NotFoundRedirects $payload */
        $payload = $response->getPayload();

        self::assertEquals($expectedResult, $payload->jsonSerialize());
        $getOrCreate->shouldHaveBeenCalledOnce();
        $configureNotFoundRedirects->shouldHaveBeenCalledOnce();
    }

    public function provideDomainsAndRedirects(): iterable
    {
        yield 'full overwrite' => [Domain::withAuthority(''), [
            DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'foo',
            DomainRedirectsInputFilter::REGULAR_404_REDIRECT => 'bar',
            DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'baz',
        ], [
            DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'foo',
            DomainRedirectsInputFilter::REGULAR_404_REDIRECT => 'bar',
            DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'baz',
        ]];
        yield 'partial overwrite' => [Domain::withAuthority(''), [
            DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'foo',
            DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'baz',
        ], [
            DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'foo',
            DomainRedirectsInputFilter::REGULAR_404_REDIRECT => null,
            DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'baz',
        ]];
        yield 'no override' => [
            (static function (): Domain {
                $domain = Domain::withAuthority('');
                $domain->configureNotFoundRedirects(NotFoundRedirects::withRedirects(
                    'baz',
                    'bar',
                    'foo',
                ));

                return $domain;
            })(),
            [],
            [
                DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'baz',
                DomainRedirectsInputFilter::REGULAR_404_REDIRECT => 'bar',
                DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'foo',
            ],
        ];
        yield 'reset' => [
            (static function (): Domain {
                $domain = Domain::withAuthority('');
                $domain->configureNotFoundRedirects(NotFoundRedirects::withRedirects(
                    'foo',
                    'bar',
                    'baz',
                ));

                return $domain;
            })(),
            [
                DomainRedirectsInputFilter::BASE_URL_REDIRECT => null,
                DomainRedirectsInputFilter::REGULAR_404_REDIRECT => null,
                DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => null,
            ],
            [
                DomainRedirectsInputFilter::BASE_URL_REDIRECT => null,
                DomainRedirectsInputFilter::REGULAR_404_REDIRECT => null,
                DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => null,
            ],
        ];
    }
}
