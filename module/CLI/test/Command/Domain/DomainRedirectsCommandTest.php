<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Domain\DomainRedirectsCommand;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Config\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

use function substr_count;

class DomainRedirectsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & DomainServiceInterface $domainService;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(DomainServiceInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new DomainRedirectsCommand($this->domainService));
    }

    #[Test, DataProvider('provideDomains')]
    public function onlyPlainQuestionsAreAskedForNewDomainsAndDomainsWithNoRedirects(Domain|null $domain): void
    {
        $domainAuthority = 'my-domain.com';
        $this->domainService->expects($this->once())->method('findByAuthority')->with($domainAuthority)->willReturn(
            $domain,
        );
        $this->domainService->expects($this->once())->method('configureNotFoundRedirects')->with(
            $domainAuthority,
            NotFoundRedirects::withRedirects('foo.com', null, 'baz.com'),
        )->willReturn(Domain::withAuthority(''));
        $this->domainService->expects($this->never())->method('listDomains');

        $this->commandTester->setInputs(['foo.com', '', 'baz.com']);
        $this->commandTester->execute(['domain' => $domainAuthority]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('[OK] "Not found" redirects properly set for "my-domain.com"', $output);
        self::assertStringContainsString('URL to redirect to when a user hits this domain\'s base URL', $output);
        self::assertStringContainsString(
            'URL to redirect to when a user hits a not found URL other than an invalid short URL',
            $output,
        );
        self::assertStringContainsString('URL to redirect to when a user hits an invalid short URL', $output);
        self::assertEquals(3, substr_count($output, '(Leave empty for no redirect)'));
    }

    public static function provideDomains(): iterable
    {
        yield 'no domain' => [null];
        yield 'domain without redirects' => [Domain::withAuthority('')];
    }

    #[Test]
    public function offersNewOptionsForDomainsWithExistingRedirects(): void
    {
        $domainAuthority = 'example.com';
        $domain = Domain::withAuthority($domainAuthority);
        $domain->configureNotFoundRedirects(NotFoundRedirects::withRedirects('foo.com', 'bar.com', 'baz.com'));

        $this->domainService->expects($this->once())->method('findByAuthority')->with($domainAuthority)->willReturn(
            $domain,
        );
        $this->domainService->expects($this->once())->method('configureNotFoundRedirects')->with(
            $domainAuthority,
            NotFoundRedirects::withRedirects(null, 'edited.com', 'baz.com'),
        )->willReturn($domain);
        $this->domainService->expects($this->never())->method('listDomains');

        $this->commandTester->setInputs(['2', '1', 'edited.com', '0']);
        $this->commandTester->execute(['domain' => $domainAuthority]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('[OK] "Not found" redirects properly set for "example.com"', $output);
        self::assertStringContainsString('Keep current one: [bar.com]', $output);
        self::assertStringContainsString('Keep current one: [baz.com]', $output);
        self::assertStringContainsString('Keep current one: [baz.com]', $output);
        self::assertStringNotContainsStringIgnoringCase('(Leave empty for no redirect)', $output);
        self::assertEquals(3, substr_count($output, 'Set new redirect URL'));
        self::assertEquals(3, substr_count($output, 'Remove redirect'));
    }

    #[Test]
    public function authorityIsRequestedWhenNotProvidedAndNoOtherDomainsExist(): void
    {
        $domainAuthority = 'example.com';
        $domain = Domain::withAuthority($domainAuthority);

        $this->domainService->expects($this->once())->method('listDomains')->with()->willReturn([]);
        $this->domainService->expects($this->once())->method('findByAuthority')->with($domainAuthority)->willReturn(
            $domain,
        );
        $this->domainService->expects($this->once())->method('configureNotFoundRedirects')->with(
            $domainAuthority,
            NotFoundRedirects::withoutRedirects(),
        )->willReturn($domain);

        $this->commandTester->setInputs([$domainAuthority, '', '', '']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Domain authority for which you want to set specific redirects', $output);
    }

    #[Test]
    public function oneOfTheExistingDomainsCanBeSelected(): void
    {
        $domainAuthority = 'existing-two.com';
        $domain = Domain::withAuthority($domainAuthority);

        $this->domainService->expects($this->once())->method('listDomains')->with()->willReturn([
            DomainItem::forDefaultDomain('default-domain.com', new NotFoundRedirectOptions()),
            DomainItem::forNonDefaultDomain(Domain::withAuthority('existing-one.com')),
            DomainItem::forNonDefaultDomain(Domain::withAuthority($domainAuthority)),
        ]);
        $this->domainService->expects($this->once())->method('findByAuthority')->with($domainAuthority)->willReturn(
            $domain,
        );
        $this->domainService->expects($this->once())->method('configureNotFoundRedirects')->with(
            $domainAuthority,
            NotFoundRedirects::withoutRedirects(),
        )->willReturn($domain);

        $this->commandTester->setInputs(['1', '', '', '']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringNotContainsString('Domain authority for which you want to set specific redirects', $output);
        self::assertStringNotContainsString('default-domain.com', $output);
        self::assertStringContainsString('existing-one.com', $output);
        self::assertStringContainsString($domainAuthority, $output);
    }

    #[Test]
    public function aNewDomainCanBeCreatedEvenIfOthersAlreadyExist(): void
    {
        $domainAuthority = 'new-domain.com';
        $domain = Domain::withAuthority($domainAuthority);

        $this->domainService->expects($this->once())->method('listDomains')->with()->willReturn([
            DomainItem::forDefaultDomain('default-domain.com', new NotFoundRedirectOptions()),
            DomainItem::forNonDefaultDomain(Domain::withAuthority('existing-one.com')),
            DomainItem::forNonDefaultDomain(Domain::withAuthority('existing-two.com')),
        ]);
        $this->domainService->expects($this->once())->method('findByAuthority')->with($domainAuthority)->willReturn(
            $domain,
        );
        $this->domainService->expects($this->once())->method('configureNotFoundRedirects')->with(
            $domainAuthority,
            NotFoundRedirects::withoutRedirects(),
        )->willReturn($domain);

        $this->commandTester->setInputs(['2', $domainAuthority, '', '', '']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Domain authority for which you want to set specific redirects', $output);
        self::assertStringNotContainsString('default-domain.com', $output);
        self::assertStringContainsString('existing-one.com', $output);
        self::assertStringContainsString('existing-two.com', $output);
    }
}
