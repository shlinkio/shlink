<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Config;

use Closure;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Interact;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Shlinkio\Shlink\Config\formatEnvVarValue;
use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\enumValues;
use function sprintf;

#[AsCommand(
    name: ReadEnvVarCommand::NAME,
    description: 'Display current value for an env var',
    hidden: true,
)]
class ReadEnvVarCommand extends Command
{
    public const string NAME = 'env-var:read';

    /** @var Closure(string $envVar): mixed */
    private readonly Closure $loadEnvVar;

    public function __construct(Closure|null $loadEnvVar = null)
    {
        $this->loadEnvVar = $loadEnvVar ?? static fn (string $envVar) => EnvVars::from($envVar)->loadFromEnv();
        parent::__construct();
    }

    #[Interact]
    public function askMissing(InputInterface $input, SymfonyStyle $io): void
    {
        $envVar = $input->getArgument('env-var');
        $validEnvVars = enumValues(EnvVars::class);

        if ($envVar === null) {
            $envVar = $io->choice('Select the env var to read', $validEnvVars);
        }

        if (! contains($envVar, $validEnvVars)) {
            throw new InvalidArgumentException(sprintf('%s is not a valid Shlink environment variable', $envVar));
        }

        $input->setArgument('env-var', $envVar);
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The env var to read')] string $envVar,
    ): int {
        $io->writeln(formatEnvVarValue(($this->loadEnvVar)($envVar)));
        return Command::SUCCESS;
    }
}
