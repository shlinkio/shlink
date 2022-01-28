<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\CreateShortUrlCommand;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifierInterface;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

class CreateShortUrlCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private const DEFAULT_DOMAIN = 'default.com';

    private CommandTester $commandTester;
    private ObjectProphecy $urlShortener;
    private ObjectProphecy $stringifier;

    public function setUp(): void
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $this->stringifier = $this->prophesize(ShortUrlStringifierInterface::class);
        $this->stringifier->stringify(Argument::type(ShortUrl::class))->willReturn('');

        $command = new CreateShortUrlCommand(
            $this->urlShortener->reveal(),
            $this->stringifier->reveal(),
            5,
            self::DEFAULT_DOMAIN,
        );
        $this->commandTester = $this->testerForCommand($command);
    }

    /** @test */
    public function properShortCodeIsCreatedIfLongUrlIsCorrect(): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $urlToShortCode = $this->urlShortener->shorten(Argument::cetera())->willReturn($shortUrl);
        $stringify = $this->stringifier->stringify($shortUrl)->willReturn('stringified_short_url');

        $this->commandTester->execute([
            'longUrl' => 'http://domain.com/foo/bar',
            '--max-visits' => '3',
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('stringified_short_url', $output);
        $urlToShortCode->shouldHaveBeenCalledOnce();
        $stringify->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function exceptionWhileParsingLongUrlOutputsError(): void
    {
        $url = 'http://domain.com/invalid';
        $this->urlShortener->shorten(Argument::cetera())->willThrow(InvalidUrlException::fromUrl($url))
                                                               ->shouldBeCalledOnce();

        $this->commandTester->execute(['longUrl' => $url]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCodes::EXIT_FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Provided URL http://domain.com/invalid is invalid.', $output);
    }

    /** @test */
    public function providingNonUniqueSlugOutputsError(): void
    {
        $urlToShortCode = $this->urlShortener->shorten(Argument::cetera())->willThrow(
            NonUniqueSlugException::fromSlug('my-slug'),
        );

        $this->commandTester->execute(['longUrl' => 'http://domain.com/invalid', '--custom-slug' => 'my-slug']);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCodes::EXIT_FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Provided slug "my-slug" is already in use', $output);
        $urlToShortCode->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function properlyProcessesProvidedTags(): void
    {
        $shortUrl = ShortUrl::createEmpty();
        $urlToShortCode = $this->urlShortener->shorten(
            Argument::that(function (ShortUrlMeta $meta) {
                $tags = $meta->getTags();
                Assert::assertEquals(['foo', 'bar', 'baz', 'boo', 'zar'], $tags);
                return true;
            }),
        )->willReturn($shortUrl);
        $stringify = $this->stringifier->stringify($shortUrl)->willReturn('stringified_short_url');

        $this->commandTester->execute([
            'longUrl' => 'http://domain.com/foo/bar',
            '--tags' => ['foo,bar', 'baz', 'boo,zar,baz'],
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('stringified_short_url', $output);
        $urlToShortCode->shouldHaveBeenCalledOnce();
        $stringify->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideDomains
     */
    public function properlyProcessesProvidedDomain(array $input, ?string $expectedDomain): void
    {
        $shorten = $this->urlShortener->shorten(
            Argument::that(function (ShortUrlMeta $meta) use ($expectedDomain) {
                Assert::assertEquals($expectedDomain, $meta->getDomain());
                return true;
            }),
        )->willReturn(ShortUrl::createEmpty());

        $input['longUrl'] = 'http://domain.com/foo/bar';
        $this->commandTester->execute($input);

        self::assertEquals(ExitCodes::EXIT_SUCCESS, $this->commandTester->getStatusCode());
        $shorten->shouldHaveBeenCalledOnce();
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
        $urlToShortCode = $this->urlShortener->shorten(
            Argument::that(function (ShortUrlMeta $meta) use ($expectedValidateUrl) {
                Assert::assertEquals($expectedValidateUrl, $meta->doValidateUrl());
                return $meta;
            }),
        )->willReturn($shortUrl);

        $options['longUrl'] = 'http://domain.com/foo/bar';
        $this->commandTester->execute($options);

        $urlToShortCode->shouldHaveBeenCalledOnce();
    }

    public function provideFlags(): iterable
    {
        yield 'no flags' => [[], null];
        yield 'validate-url' => [['--validate-url' => true], true];
    }
}
