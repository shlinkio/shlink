<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class DisableKeyCommand extends Command
{
    public const NAME = 'api-key:disable';

    public function __construct(private ApiKeyServiceInterface $apiKeyService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::NAME)
             ->setDescription('Disables an API key.')
             ->addArgument('apiKey', InputArgument::REQUIRED, 'The API key to disable');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $apiKey = $input->getArgument('apiKey');
        $io = new SymfonyStyle($input, $output);

        try {
            $this->apiKeyService->disable($apiKey);
            $io->success(sprintf('API key "%s" properly disabled', $apiKey));
            return ExitCodes::EXIT_SUCCESS;
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return ExitCodes::EXIT_FAILURE;
        }
    }
}
