<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Core\Exception;
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
    private const ALIASES = ['short-code:delete'];

    private DeleteShortUrlServiceInterface $deleteShortUrlService;

    public function __construct(DeleteShortUrlServiceInterface $deleteShortUrlService)
    {
        parent::__construct();
        $this->deleteShortUrlService = $deleteShortUrlService;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setAliases(self::ALIASES)
            ->setDescription('Deletes a short URL')
            ->addArgument('shortCode', InputArgument::REQUIRED, 'The short code for the short URL to be deleted')
            ->addOption(
                'ignore-threshold',
                'i',
                InputOption::VALUE_NONE,
                'Ignores the safety visits threshold check, which could make short URLs with many visits to be '
                . 'accidentally deleted'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $shortCode = $input->getArgument('shortCode');
        $ignoreThreshold = $input->getOption('ignore-threshold');

        try {
            $this->runDelete($io, $shortCode, $ignoreThreshold);
            return ExitCodes::EXIT_SUCCESS;
        } catch (Exception\ShortUrlNotFoundException $e) {
            $io->error($e->getMessage());
            return ExitCodes::EXIT_FAILURE;
        } catch (Exception\DeleteShortUrlException $e) {
            return $this->retry($io, $shortCode, $e->getMessage());
        }
    }

    private function retry(SymfonyStyle $io, string $shortCode, string $warningMsg): int
    {
        $io->writeln(sprintf('<bg=yellow>%s</>', $warningMsg));
        $forceDelete = $io->confirm('Do you want to delete it anyway?', false);

        if ($forceDelete) {
            $this->runDelete($io, $shortCode, true);
        } else {
            $io->warning('Short URL was not deleted.');
        }

        return $forceDelete ? ExitCodes::EXIT_SUCCESS : ExitCodes::EXIT_WARNING;
    }

    private function runDelete(SymfonyStyle $io, string $shortCode, bool $ignoreThreshold): void
    {
        $this->deleteShortUrlService->deleteByShortCode($shortCode, $ignoreThreshold);
        $io->success(sprintf('Short URL with short code "%s" successfully deleted.', $shortCode));
    }
}
