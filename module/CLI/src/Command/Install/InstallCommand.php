<?php
namespace Shlinkio\Shlink\CLI\Command\Install;

class InstallCommand extends AbstractInstallCommand
{
    /**
     * @return bool
     */
    protected function isUpdate()
    {
        return false;
    }
}
