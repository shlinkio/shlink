<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Validation\DomainRedirectsInputFilter;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Rest\Action\Domain\DomainRedirectsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function array_key_exists;

class DomainRedirectsActionTest extends TestCase
{
    private DomainRedirectsAction $action;
    private MockObject & DomainServiceInterface $domainService;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainServiceInterface::class);
        $this->action = new DomainRedirectsAction($this->domainService);
    }

    #[Test, DataProvider('provideInvalidBodies')]
    public function invalidDataThrowsException(array $body): void
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body);

        $this->expectException(ValidationException::class);
        $this->domainService->expects($this->never())->method('getOrCreate');
        $this->domainService->expects($this->never())->method('configureNotFoundRedirects');

        $this->action->handle($request);
    }

    public static function provideInvalidBodies(): iterable
    {
        yield 'no domain' => [[]];
        yield 'empty domain' => [['domain' => '']];
        yield 'invalid domain' => [['domain' => '192.168.1.20']];
    }

    #[Test, DataProvider('provideDomainsAndRedirects')]
    public function domainIsFetchedAndUsedToGetItConfigured(
        Domain $domain,
        array $redirects,
        array $expectedResult,
    ): void {
        $authority = 's.test';
        $redirects['domain'] = $authority;
        $apiKey = ApiKey::create();
        $request = ServerRequestFactory::fromGlobals()->withParsedBody($redirects)
                                                      ->withAttribute(ApiKey::class, $apiKey);

        $this->domainService->expects($this->once())->method('getOrCreate')->with($authority)->willReturn($domain);
        $this->domainService->expects($this->once())->method('configureNotFoundRedirects')->with(
            $authority,
            NotFoundRedirects::withRedirects(
                array_key_exists(DomainRedirectsInputFilter::BASE_URL_REDIRECT, $redirects)
                    ? $redirects[DomainRedirectsInputFilter::BASE_URL_REDIRECT]
                    : $domain->baseUrlRedirect(),
                array_key_exists(DomainRedirectsInputFilter::REGULAR_404_REDIRECT, $redirects)
                    ? $redirects[DomainRedirectsInputFilter::REGULAR_404_REDIRECT]
                    : $domain->regular404Redirect(),
                array_key_exists(DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT, $redirects)
                    ? $redirects[DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT]
                    : $domain->invalidShortUrlRedirect(),
                array_key_exists(DomainRedirectsInputFilter::EXPIRED_SHORT_URL_REDIRECT, $redirects)
                    ? $redirects[DomainRedirectsInputFilter::EXPIRED_SHORT_URL_REDIRECT]
                    : $domain->expiredShortUrlRedirect(),
            ),
            $apiKey,
        );

        /** @var JsonResponse $response */
        $response = $this->action->handle($request);
        /** @var NotFoundRedirects $payload */
        $payload = $response->getPayload();

        self::assertEquals($expectedResult, $payload->jsonSerialize());
    }

    public static function provideDomainsAndRedirects(): iterable
    {
        yield 'full overwrite' => [Domain::withAuthority(''), [
            DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'foo',
            DomainRedirectsInputFilter::REGULAR_404_REDIRECT => 'bar',
            DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'baz',
            DomainRedirectsInputFilter::EXPIRED_SHORT_URL_REDIRECT => 'qux',
        ], [
            DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'foo',
            DomainRedirectsInputFilter::REGULAR_404_REDIRECT => 'bar',
            DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'baz',
            DomainRedirectsInputFilter::EXPIRED_SHORT_URL_REDIRECT => 'qux',
        ]];
        yield 'partial overwrite' => [Domain::withAuthority(''), [
            DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'foo',
            DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'baz',
        ], [
            DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'foo',
            DomainRedirectsInputFilter::REGULAR_404_REDIRECT => null,
            DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'baz',
            DomainRedirectsInputFilter::EXPIRED_SHORT_URL_REDIRECT => null,
        ]];
        yield 'no override' => [
            (static function (): Domain {
                $domain = Domain::withAuthority('');
                $domain->configureNotFoundRedirects(NotFoundRedirects::withRedirects(
                    'baz',
                    'bar',
                    'foo',
                    'qux',
                ));

                return $domain;
            })(),
            [],
            [
                DomainRedirectsInputFilter::BASE_URL_REDIRECT => 'baz',
                DomainRedirectsInputFilter::REGULAR_404_REDIRECT => 'bar',
                DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => 'foo',
                DomainRedirectsInputFilter::EXPIRED_SHORT_URL_REDIRECT => 'qux',
            ],
        ];
        yield 'reset' => [
            (static function (): Domain {
                $domain = Domain::withAuthority('');
                $domain->configureNotFoundRedirects(NotFoundRedirects::withRedirects(
                    'foo',
                    'bar',
                    'baz',
                    'qux',
                ));

                return $domain;
            })(),
            [
                DomainRedirectsInputFilter::BASE_URL_REDIRECT => null,
                DomainRedirectsInputFilter::REGULAR_404_REDIRECT => null,
                DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => null,
                DomainRedirectsInputFilter::EXPIRED_SHORT_URL_REDIRECT => null,
            ],
            [
                DomainRedirectsInputFilter::BASE_URL_REDIRECT => null,
                DomainRedirectsInputFilter::REGULAR_404_REDIRECT => null,
                DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT => null,
                DomainRedirectsInputFilter::EXPIRED_SHORT_URL_REDIRECT => null,
            ],
        ];
    }
}
