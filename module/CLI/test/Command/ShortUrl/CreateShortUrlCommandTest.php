<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\CreateShortUrlCommand;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\UrlShorteningResult;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortenerInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class CreateShortUrlCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & UrlShortenerInterface $urlShortener;
    private MockObject & ShortUrlStringifierInterface $stringifier;

    protected function setUp(): void
    {
        $this->urlShortener = $this->createMock(UrlShortenerInterface::class);
        $this->stringifier = $this->createMock(ShortUrlStringifierInterface::class);

        $command = new CreateShortUrlCommand(
            $this->urlShortener,
            $this->stringifier,
            new UrlShortenerOptions(
                domain: ['hostname' => 'example.com', 'schema' => ''],
                defaultShortCodesLength: 5,
            ),
        );
        $this->commandTester = CliTestUtils::testerForCommand($command);
    }

    #[Test]
    public function properShortCodeIsCreatedIfLongUrlIsCorrect(): void
    {
        $shortUrl = ShortUrl::createFake();
        $this->urlShortener->expects($this->once())->method('shorten')->withAnyParameters()->willReturn(
            UrlShorteningResult::withoutErrorOnEventDispatching($shortUrl),
        );
        $this->stringifier->expects($this->once())->method('stringify')->with($shortUrl)->willReturn(
            'stringified_short_url',
        );

        $this->commandTester->execute([
            'longUrl' => 'http://domain.com/foo/bar',
            '--max-visits' => '3',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCode::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('stringified_short_url', $output);
        self::assertStringNotContainsString('but the real-time updates cannot', $output);
    }

    #[Test]
    public function exceptionWhileParsingLongUrlOutputsError(): void
    {
        $url = 'http://domain.com/invalid';
        $this->urlShortener->expects($this->once())->method('shorten')->withAnyParameters()->willThrowException(
            InvalidUrlException::fromUrl($url),
        );
        $this->stringifier->method('stringify')->with($this->isInstanceOf(ShortUrl::class))->willReturn('');

        $this->commandTester->execute(['longUrl' => $url]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCode::EXIT_FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Provided URL http://domain.com/invalid is invalid.', $output);
    }

    #[Test]
    public function providingNonUniqueSlugOutputsError(): void
    {
        $this->urlShortener->expects($this->once())->method('shorten')->withAnyParameters()->willThrowException(
            NonUniqueSlugException::fromSlug('my-slug'),
        );
        $this->stringifier->method('stringify')->with($this->isInstanceOf(ShortUrl::class))->willReturn('');

        $this->commandTester->execute(['longUrl' => 'http://domain.com/invalid', '--custom-slug' => 'my-slug']);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCode::EXIT_FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Provided slug "my-slug" is already in use', $output);
    }

    #[Test]
    public function properlyProcessesProvidedTags(): void
    {
        $shortUrl = ShortUrl::createFake();
        $this->urlShortener->expects($this->once())->method('shorten')->with(
            $this->callback(function (ShortUrlCreation $creation) {
                Assert::assertEquals(['foo', 'bar', 'baz', 'boo', 'zar'], $creation->tags);
                return true;
            }),
        )->willReturn(UrlShorteningResult::withoutErrorOnEventDispatching($shortUrl));
        $this->stringifier->expects($this->once())->method('stringify')->with($shortUrl)->willReturn(
            'stringified_short_url',
        );

        $this->commandTester->execute([
            'longUrl' => 'http://domain.com/foo/bar',
            '--tags' => ['foo,bar', 'baz', 'boo,zar,baz'],
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCode::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('stringified_short_url', $output);
    }

    #[Test, DataProvider('provideDomains')]
    public function properlyProcessesProvidedDomain(array $input, ?string $expectedDomain): void
    {
        $this->urlShortener->expects($this->once())->method('shorten')->with(
            $this->callback(function (ShortUrlCreation $meta) use ($expectedDomain) {
                Assert::assertEquals($expectedDomain, $meta->domain);
                return true;
            }),
        )->willReturn(UrlShorteningResult::withoutErrorOnEventDispatching(ShortUrl::createFake()));
        $this->stringifier->method('stringify')->with($this->isInstanceOf(ShortUrl::class))->willReturn('');

        $input['longUrl'] = 'http://domain.com/foo/bar';
        $this->commandTester->execute($input);

        self::assertEquals(ExitCode::EXIT_SUCCESS, $this->commandTester->getStatusCode());
    }

    public static function provideDomains(): iterable
    {
        yield 'no domain' => [[], null];
        yield 'domain foo' => [['--domain' => 'foo.com'], 'foo.com'];
        yield 'domain bar' => [['-d' => 'bar.com'], 'bar.com'];
    }

    #[Test, DataProvider('provideFlags')]
    public function urlValidationHasExpectedValueBasedOnProvidedFlags(array $options, ?bool $expectedValidateUrl): void
    {
        $shortUrl = ShortUrl::createFake();
        $this->urlShortener->expects($this->once())->method('shorten')->with(
            $this->callback(function (ShortUrlCreation $meta) use ($expectedValidateUrl) {
                Assert::assertEquals($expectedValidateUrl, $meta->doValidateUrl());
                return true;
            }),
        )->willReturn(UrlShorteningResult::withoutErrorOnEventDispatching($shortUrl));
        $this->stringifier->method('stringify')->with($this->isInstanceOf(ShortUrl::class))->willReturn('');

        $options['longUrl'] = 'http://domain.com/foo/bar';
        $this->commandTester->execute($options);
    }

    public static function provideFlags(): iterable
    {
        yield 'no flags' => [[], null];
        yield 'validate-url' => [['--validate-url' => true], true];
    }

    /**
     * @param callable(string $output): void $assert
     */
    #[Test, DataProvider('provideDispatchBehavior')]
    public function warningIsPrintedInVerboseModeWhenDispatchErrors(int $verbosity, callable $assert): void
    {
        $shortUrl = ShortUrl::createFake();
        $this->urlShortener->expects($this->once())->method('shorten')->withAnyParameters()->willReturn(
            UrlShorteningResult::withErrorOnEventDispatching($shortUrl, new ServiceNotFoundException()),
        );
        $this->stringifier->method('stringify')->willReturn('stringified_short_url');

        $this->commandTester->execute(['longUrl' => 'http://domain.com/foo/bar'], ['verbosity' => $verbosity]);
        $output = $this->commandTester->getDisplay();

        $assert($output);
    }

    public static function provideDispatchBehavior(): iterable
    {
        $containsAssertion = static fn (string $output) => self::assertStringContainsString(
            'but the real-time updates cannot',
            $output,
        );
        $doesNotContainAssertion = static fn (string $output) => self::assertStringNotContainsString(
            'but the real-time updates cannot',
            $output,
        );

        yield 'quiet' => [OutputInterface::VERBOSITY_QUIET, $doesNotContainAssertion];
        yield 'normal' => [OutputInterface::VERBOSITY_NORMAL, $doesNotContainAssertion];
        yield 'verbose' => [OutputInterface::VERBOSITY_VERBOSE, $containsAssertion];
        yield 'very verbose' => [OutputInterface::VERBOSITY_VERY_VERBOSE, $containsAssertion];
        yield 'debug' => [OutputInterface::VERBOSITY_DEBUG, $containsAssertion];
    }
}
