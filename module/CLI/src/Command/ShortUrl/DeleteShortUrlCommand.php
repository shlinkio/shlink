<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(name: DeleteShortUrlCommand::NAME, description: 'Deletes a short URL')]
class DeleteShortUrlCommand extends Command
{
    public const string NAME = 'short-url:delete';

    public function __construct(private readonly DeleteShortUrlServiceInterface $deleteShortUrlService)
    {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The short code for the short URL to be deleted')] string $shortCode,
        #[Option('The domain if the short code does not belong to the default one', shortcut: 'd')]
        string|null $domain = null,
        #[Option(
            'Ignores the safety visits threshold check, which could make short URLs with many visits to be '
            . 'accidentally deleted',
            shortcut: 'i',
        )]
        bool $ignoreThreshold = false,
    ): int {
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode, $domain);

        try {
            $this->runDelete($io, $identifier, $ignoreThreshold);
            return self::SUCCESS;
        } catch (Exception\ShortUrlNotFoundException $e) {
            $io->error($e->getMessage());
            return self::FAILURE;
        } catch (Exception\DeleteShortUrlException $e) {
            return $this->retry($io, $identifier, $e->getMessage());
        }
    }

    private function retry(SymfonyStyle $io, ShortUrlIdentifier $identifier, string $warningMsg): int
    {
        $io->writeln(sprintf('<bg=yellow>%s</>', $warningMsg));
        $forceDelete = $io->confirm('Do you want to delete it anyway?', false);

        if ($forceDelete) {
            $this->runDelete($io, $identifier, true);
        } else {
            $io->warning('Short URL was not deleted.');
        }

        return $forceDelete ? self::SUCCESS : self::INVALID;
    }

    private function runDelete(SymfonyStyle $io, ShortUrlIdentifier $identifier, bool $ignoreThreshold): void
    {
        $this->deleteShortUrlService->deleteByShortCode($identifier, $ignoreThreshold);
        $io->success(sprintf('Short URL with short code "%s" successfully deleted.', $identifier->shortCode));
    }
}
