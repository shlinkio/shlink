<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitialApiKeyCommand extends Command
{
    public const string NAME = 'api-key:initial';

    public function __construct(private readonly ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHidden()
            ->setName(self::NAME)
            ->setDescription('Tries to create initial API key')
            ->addArgument('apiKey', InputArgument::REQUIRED, 'The initial API to create');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('apiKey');
        $result = $this->apiKeyService->createInitial($key);

        if ($result === null && $output->isVerbose()) {
            $output->writeln('<comment>Other API keys already exist. Initial API key creation skipped.</comment>');
        }

        return Command::SUCCESS;
    }
}
