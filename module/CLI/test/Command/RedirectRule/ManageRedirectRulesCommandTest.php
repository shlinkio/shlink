<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\RedirectRule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\RedirectRule\ManageRedirectRulesCommand;
use Shlinkio\Shlink\CLI\RedirectRule\RedirectRuleHandlerInterface;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

class ManageRedirectRulesCommandTest extends TestCase
{
    private ShortUrlResolverInterface & MockObject $shortUrlResolver;
    private ShortUrlRedirectRuleServiceInterface & MockObject $ruleService;
    private RedirectRuleHandlerInterface & MockObject $ruleHandler;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->shortUrlResolver = $this->createMock(ShortUrlResolverInterface::class);
        $this->ruleService = $this->createMock(ShortUrlRedirectRuleServiceInterface::class);
        $this->ruleHandler = $this->createMock(RedirectRuleHandlerInterface::class);

        $this->commandTester = CliTestUtils::testerForCommand(new ManageRedirectRulesCommand(
            $this->shortUrlResolver,
            $this->ruleService,
            $this->ruleHandler,
        ));
    }

    #[Test]
    public function errorIsReturnedIfShortUrlCannotBeFound(): void
    {
        $this->shortUrlResolver->expects($this->once())->method('resolveShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('foo'),
        )->willThrowException(new ShortUrlNotFoundException(''));
        $this->ruleService->expects($this->never())->method('rulesForShortUrl');
        $this->ruleService->expects($this->never())->method('saveRulesForShortUrl');
        $this->ruleHandler->expects($this->never())->method('manageRules');

        $exitCode = $this->commandTester->execute(['shortCode' => 'foo']);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCode::EXIT_FAILURE, $exitCode);
        self::assertStringContainsString('Short URL for foo not found', $output);
    }

    #[Test]
    public function savesNoRulesIfManageResultIsNull(): void
    {
        $shortUrl = ShortUrl::withLongUrl('https://example.com');

        $this->shortUrlResolver->expects($this->once())->method('resolveShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('foo'),
        )->willReturn($shortUrl);
        $this->ruleService->expects($this->once())->method('rulesForShortUrl')->with($shortUrl)->willReturn([]);
        $this->ruleHandler->expects($this->once())->method('manageRules')->willReturn(null);
        $this->ruleService->expects($this->never())->method('saveRulesForShortUrl');

        $exitCode = $this->commandTester->execute(['shortCode' => 'foo']);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCode::EXIT_SUCCESS, $exitCode);
        self::assertStringNotContainsString('Rules properly saved', $output);
    }

    #[Test]
    public function savesRulesIfManageResultIsAnArray(): void
    {
        $shortUrl = ShortUrl::withLongUrl('https://example.com');

        $this->shortUrlResolver->expects($this->once())->method('resolveShortUrl')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain('foo'),
        )->willReturn($shortUrl);
        $this->ruleService->expects($this->once())->method('rulesForShortUrl')->with($shortUrl)->willReturn([]);
        $this->ruleHandler->expects($this->once())->method('manageRules')->willReturn([]);
        $this->ruleService->expects($this->once())->method('saveRulesForShortUrl')->with($shortUrl, []);

        $exitCode = $this->commandTester->execute(['shortCode' => 'foo']);
        $output = $this->commandTester->getDisplay();

        self::assertEquals(ExitCode::EXIT_SUCCESS, $exitCode);
        self::assertStringContainsString('Rules properly saved', $output);
    }
}
