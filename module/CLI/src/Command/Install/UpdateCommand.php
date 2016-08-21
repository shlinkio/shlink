<?php
namespace Shlinkio\Shlink\CLI\Command\Install;

use Zend\Config\Writer\WriterInterface;

class UpdateCommand extends InstallCommand
{
    public function createDatabase()
    {
        return true;
    }
}
