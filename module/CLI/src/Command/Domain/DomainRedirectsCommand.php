<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Domain;

use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Core\Domain\Model\DomainItem;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Interact;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_filter;
use function array_map;
use function sprintf;
use function str_contains;

#[AsCommand(
    name: DomainRedirectsCommand::NAME,
    description: 'Set specific "not found" redirects for individual domains.',
)]
class DomainRedirectsCommand extends Command
{
    public const string NAME = 'domain:redirects';

    public function __construct(private readonly DomainServiceInterface $domainService)
    {
        parent::__construct();
    }

    #[Interact]
    public function askDomain(InputInterface $input, SymfonyStyle $io): void
    {
        /** @var string|null $domain */
        $domain = $input->getArgument('domain');
        if ($domain !== null) {
            return;
        }

        $askNewDomain = static fn () => $io->ask('Domain authority for which you want to set specific redirects');

        /** @var string[] $availableDomains */
        $availableDomains = array_map(
            static fn (DomainItem $item) => $item->toString(),
            array_filter($this->domainService->listDomains(), static fn (DomainItem $item) => ! $item->isDefault),
        );
        if (empty($availableDomains)) {
            $input->setArgument('domain', $askNewDomain());
            return;
        }

        $selectedOption = $io->choice(
            'Select the domain to configure',
            [...$availableDomains, '<options=bold>New domain</>'],
        );
        $input->setArgument('domain', str_contains($selectedOption, 'New domain') ? $askNewDomain() : $selectedOption);
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The domain authority to which you want to set the specific redirects', name: 'domain')]
        string $domainAuthority,
    ): int {
        $domain = $this->domainService->findByAuthority($domainAuthority);

        $ask = static function (string $message, string|null $current) use ($io): string|null {
            if ($current === null) {
                return $io->ask(sprintf('%s (Leave empty for no redirect)', $message));
            }

            $choice = $io->choice($message, [
                sprintf('Keep current one: [%s]', $current),
                'Set new redirect URL',
                'Remove redirect',
            ]);

            return match ($choice) {
                'Set new redirect URL' => $io->ask('New redirect URL'),
                'Remove redirect' => null,
                default => $current,
            };
        };

        $this->domainService->configureNotFoundRedirects($domainAuthority, NotFoundRedirects::withRedirects(
            $ask(
                'URL to redirect to when a user hits this domain\'s base URL',
                $domain?->baseUrlRedirect,
            ),
            $ask(
                'URL to redirect to when a user hits a not found URL other than an invalid short URL',
                $domain?->regular404Redirect,
            ),
            $ask(
                'URL to redirect to when a user hits an invalid short URL',
                $domain?->invalidShortUrlRedirect,
            ),
        ));

        $io->success(sprintf('"Not found" redirects properly set for "%s"', $domainAuthority));

        return self::SUCCESS;
    }
}
