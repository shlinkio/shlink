<?php
namespace Shlinkio\Shlink\CLI\Command\Install;

class InstallCommand extends AbstractInstallCommand
{
    protected function createDatabase()
    {
        $this->output->writeln('Initializing database...');
        return $this->runCommand('php vendor/bin/doctrine.php orm:schema-tool:create', 'Error generating database.');
    }
}
