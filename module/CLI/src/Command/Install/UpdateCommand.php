<?php
namespace Shlinkio\Shlink\CLI\Command\Install;

class UpdateCommand extends AbstractInstallCommand
{
    /**
     * @return bool
     */
    protected function isUpdate()
    {
        return true;
    }
}
