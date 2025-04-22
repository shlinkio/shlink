<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Input\ShortUrlIdentifierInput;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class DeleteShortUrlCommand extends Command
{
    public const string NAME = 'short-url:delete';

    private readonly ShortUrlIdentifierInput $shortUrlIdentifierInput;

    public function __construct(private readonly DeleteShortUrlServiceInterface $deleteShortUrlService)
    {
        parent::__construct();
        $this->shortUrlIdentifierInput = new ShortUrlIdentifierInput(
            $this,
            shortCodeDesc: 'The short code for the short URL to be deleted',
            domainDesc: 'The domain if the short code does not belong to the default one',
        );
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deletes a short URL')
            ->addOption(
                'ignore-threshold',
                'i',
                InputOption::VALUE_NONE,
                'Ignores the safety visits threshold check, which could make short URLs with many visits to be '
                . 'accidentally deleted',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $identifier = $this->shortUrlIdentifierInput->toShortUrlIdentifier($input);
        $ignoreThreshold = $input->getOption('ignore-threshold');

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
