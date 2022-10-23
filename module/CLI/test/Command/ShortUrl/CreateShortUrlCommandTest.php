<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\CreateShortUrlCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\UrlShortener;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class CreateShortUrlCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private const DEFAULT_DOMAIN = 'default.com';

    private CommandTester $commandTester;
    private MockObject $urlShortener;
    private MockObject $stringifier;

    protected function setUp(): void
    {
        $this->urlShortener = $this->createMock(UrlShortener::class);
        $this->stringifier = $this->createMock(ShortUrlStringifierInterface::class);

        $command = new CreateShortUrlCommand(
            $this->urlShortener,
            $this->stringifier,
            new UrlShortenerOptions(domain: ['hostname' => self::DEFAULT_DOMAIN], defaultShortCodesLength: 5),
        );
        $this->commandTester = $this->testerForCommand($command);
    }

    /** @test */
    public function properShortCodeIsCreatedIfLongUrlIsCorrect(): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $this->urlShortener->expects($this->once())->method('shorten')->withAnyParameters()->willReturn($shortUrl);
        $this->stringifier->expects($this->once())->method('stringify')->with($shortUrl)->willReturn(
            'stringified_short_url',
        );

        $this->commandTester->execute([
            'longUrl' => 'http://domain.com/foo/bar',
            '--max-visits' => '3',
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('stringified_short_url', $output);
    }

    /** @test */
    public function exceptionWhileParsingLongUrlOutputsError(): void
    {
        $url = 'http://domain.com/invalid';
        $this->urlShortener->expects($this->once())->method('shorten')->withAnyParameters()->willThrowException(
            InvalidUrlException::fromUrl($url),
        );
        $this->stringifier->method('stringify')->with($this->isInstanceOf(ShortUrl::class))->willReturn('');

        $this->commandTester->execute(['longUrl' => $url]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCodes::EXIT_FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Provided URL http://domain.com/invalid is invalid.', $output);
    }

    /** @test */
    public function providingNonUniqueSlugOutputsError(): void
    {
        $this->urlShortener->expects($this->once())->method('shorten')->withAnyParameters()->willThrowException(
            NonUniqueSlugException::fromSlug('my-slug'),
        );
        $this->stringifier->method('stringify')->with($this->isInstanceOf(ShortUrl::class))->willReturn('');

        $this->commandTester->execute(['longUrl' => 'http://domain.com/invalid', '--custom-slug' => 'my-slug']);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCodes::EXIT_FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Provided slug "my-slug" is already in use', $output);
    }

    /** @test */
    public function properlyProcessesProvidedTags(): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $this->urlShortener->expects($this->once())->method('shorten')->with(
            $this->callback(function (ShortUrlCreation $meta) {
                $tags = $meta->getTags();
                Assert::assertEquals(['foo', 'bar', 'baz', 'boo', 'zar'], $tags);
                return true;
            }),
        )->willReturn($shortUrl);
        $this->stringifier->expects($this->once())->method('stringify')->with($shortUrl)->willReturn(
            'stringified_short_url',
        );

        $this->commandTester->execute([
            'longUrl' => 'http://domain.com/foo/bar',
            '--tags' => ['foo,bar', 'baz', 'boo,zar,baz'],
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('stringified_short_url', $output);
    }

    /**
     * @test
     * @dataProvider provideDomains
     */
    public function properlyProcessesProvidedDomain(array $input, ?string $expectedDomain): void
    {
        $this->urlShortener->expects($this->once())->method('shorten')->with(
            $this->callback(function (ShortUrlCreation $meta) use ($expectedDomain) {
                Assert::assertEquals($expectedDomain, $meta->getDomain());
                return true;
            }),
        )->willReturn(ShortUrl::createEmpty());
        $this->stringifier->method('stringify')->with($this->isInstanceOf(ShortUrl::class))->willReturn('');

        $input['longUrl'] = 'http://domain.com/foo/bar';
        $this->commandTester->execute($input);

        self::assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
    }

    public function provideDomains(): iterable
    {
        yield 'no domain' => [[], null];
        yield 'non-default domain foo' => [['--domain' => 'foo.com'], 'foo.com'];
        yield 'non-default domain bar' => [['-d' => 'bar.com'], 'bar.com'];
        yield 'default domain' => [['--domain' => self::DEFAULT_DOMAIN], null];
    }

    /**
     * @test
     * @dataProvider provideFlags
     */
    public function urlValidationHasExpectedValueBasedOnProvidedFlags(array $options, ?bool $expectedValidateUrl): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $this->urlShortener->expects($this->once())->method('shorten')->with(
            $this->callback(function (ShortUrlCreation $meta) use ($expectedValidateUrl) {
                Assert::assertEquals($expectedValidateUrl, $meta->doValidateUrl());
                return true;
            }),
        )->willReturn($shortUrl);
        $this->stringifier->method('stringify')->with($this->isInstanceOf(ShortUrl::class))->willReturn('');

        $options['longUrl'] = 'http://domain.com/foo/bar';
        $this->commandTester->execute($options);
    }

    public function provideFlags(): iterable
    {
        yield 'no flags' => [[], null];
        yield 'validate-url' => [['--validate-url' => true], true];
    }
}
