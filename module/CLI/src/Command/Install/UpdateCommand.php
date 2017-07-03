<?php
namespace Shlinkio\Shlink\CLI\Command\Install;

class UpdateCommand extends AbstractInstallCommand
{
    public function createDatabase()
    {
        return true;
    }
}
