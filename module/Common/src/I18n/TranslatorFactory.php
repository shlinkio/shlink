<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\I18n;

use Interop\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

class TranslatorFactory
{
    public function __invoke(ContainerInterface $container): Translator
    {
        $config = $container->get('config');
        return Translator::factory($config['translator'] ?? []);
    }
}
