<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Config;

use Closure;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Shlinkio\Shlink\Config\formatEnvVarValue;
use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\enumValues;
use function sprintf;

class ReadEnvVarCommand extends Command
{
    public const NAME = 'env-var:read';

    /** @var Closure(string $envVar): mixed */
    private readonly Closure $loadEnvVar;

    public function __construct(?Closure $loadEnvVar = null)
    {
        $this->loadEnvVar = $loadEnvVar ?? static fn (string $envVar) => EnvVars::from($envVar)->loadFromEnv();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setHidden()
            ->setDescription('Display current value for an env var')
            ->addArgument('envVar', InputArgument::REQUIRED, 'The env var to read');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $envVar = $input->getArgument('envVar');
        $validEnvVars = enumValues(EnvVars::class);

        if ($envVar === null) {
            $envVar = $io->choice('Select the env var to read', $validEnvVars);
        }

        if (! contains($envVar, $validEnvVars)) {
            throw new InvalidArgumentException(sprintf('%s is not a valid Shlink environment variable', $envVar));
        }

        $input->setArgument('envVar', $envVar);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $envVar = $input->getArgument('envVar');
        $output->writeln(formatEnvVarValue(($this->loadEnvVar)($envVar)));

        return ExitCode::EXIT_SUCCESS;
    }
}
