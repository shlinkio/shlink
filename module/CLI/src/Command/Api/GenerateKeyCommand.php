<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Api;

use Cake\Chronos\Chronos;
use Shlinkio\Shlink\CLI\ApiKey\RoleResolverInterface;
use Shlinkio\Shlink\CLI\Util\ExitCodes;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class GenerateKeyCommand extends Command
{
    public const NAME = 'api-key:generate';
    private const HELP = <<<HELP
    The <info>%command.name%</info> generates a new valid API key.

        <info>%command.full_name%</info>

    You can optionally set its expiration date with <comment>--expirationDate</comment> or <comment>-e</comment>:

        <info>%command.full_name% --expirationDate 2020-01-01</info>

    You can also set roles to the API key:

        * Can interact with short URLs created with this API key: <info>%command.full_name% --author-only</info>
        * Can interact with short URLs for one domain only: <info>%command.full_name% --domain-only=example.com</info>
        * Both: <info>%command.full_name% --author-only --domain-only=example.com</info>
    HELP;

    private ApiKeyServiceInterface $apiKeyService;
    private RoleResolverInterface $roleResolver;

    public function __construct(ApiKeyServiceInterface $apiKeyService, RoleResolverInterface $roleResolver)
    {
        parent::__construct();
        $this->apiKeyService = $apiKeyService;
        $this->roleResolver = $roleResolver;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Generates a new valid API key.')
            ->addOption(
                'expirationDate',
                'e',
                InputOption::VALUE_REQUIRED,
                'The date in which the API key should expire. Use any valid PHP format.',
            )
            ->addOption(
                RoleResolverInterface::AUTHOR_ONLY_PARAM,
                'a',
                InputOption::VALUE_NONE,
                sprintf('Adds the "%s" role to the new API key.', Role::AUTHORED_SHORT_URLS),
            )
            ->addOption(
                RoleResolverInterface::DOMAIN_ONLY_PARAM,
                'd',
                InputOption::VALUE_REQUIRED,
                sprintf('Adds the "%s" role to the new API key, with the domain provided.', Role::DOMAIN_SPECIFIC),
            )
            ->setHelp(self::HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $expirationDate = $input->getOption('expirationDate');
        $apiKey = $this->apiKeyService->create(
            isset($expirationDate) ? Chronos::parse($expirationDate) : null,
            ...$this->roleResolver->determineRoles($input),
        );

        // TODO Print permissions that have been set
        (new SymfonyStyle($input, $output))->success(sprintf('Generated API key: "%s"', $apiKey->toString()));

        return ExitCodes::EXIT_SUCCESS;
    }
}
