<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\DeleteShortUrlServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class DeleteShortUrlCommand extends Command
{
    public const NAME = 'short-url:delete';

    public function __construct(private DeleteShortUrlServiceInterface $deleteShortUrlService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deletes a short URL')
            ->addArgument('shortCode', InputArgument::REQUIRED, 'The short code for the short URL to be deleted')
            ->addOption(
                'ignore-threshold',
                'i',
                InputOption::VALUE_NONE,
                'Ignores the safety visits threshold check, which could make short URLs with many visits to be '
                . 'accidentally deleted',
            )
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_REQUIRED,
                'The domain if the short code does not belong to the default one',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $identifier = ShortUrlIdentifier::fromCli($input);
        $ignoreThreshold = $input->getOption('ignore-threshold');

        try {
            $this->runDelete($io, $identifier, $ignoreThreshold);
            return ExitCodes::EXIT_SUCCESS;
        } catch (Exception\ShortUrlNotFoundException $e) {
            $io->error($e->getMessage());
            return ExitCodes::EXIT_FAILURE;
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

        return $forceDelete ? ExitCodes::EXIT_SUCCESS : ExitCodes::EXIT_WARNING;
    }

    private function runDelete(SymfonyStyle $io, ShortUrlIdentifier $identifier, bool $ignoreThreshold): void
    {
        $this->deleteShortUrlService->deleteByShortCode($identifier, $ignoreThreshold);
        $io->success(sprintf('Short URL with short code "%s" successfully deleted.', $identifier->shortCode()));
    }
}
