<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

use function method_exists;
use function Shlinkio\Shlink\Core\kebabCaseToCamelCase;
use function sprintf;
use function str_contains;

abstract class BaseCommand extends Command
{
    /**
     * @param mixed|null $default
     */
    protected function addOptionWithDeprecatedFallback(
        string $name,
        ?string $shortcut = null,
        ?int $mode = null,
        string $description = '',
        $default = null
    ): self {
        $this->addOption($name, $shortcut, $mode, $description, $default);

        if (str_contains($name, '-')) {
            $camelCaseName = kebabCaseToCamelCase($name);
            $this->addOption($camelCaseName, null, $mode, sprintf('[DEPRECATED] Same as "%s".', $name), $default);
        }

        return $this;
    }

    /**
     * @return bool|string|string[]|null
     */
    protected function getOptionWithDeprecatedFallback(InputInterface $input, string $name)
    {
        $rawInput = method_exists($input, '__toString') ? $input->__toString() : '';
        $camelCaseName = kebabCaseToCamelCase($name);

        if (str_contains($rawInput, $camelCaseName)) {
            return $input->getOption($camelCaseName);
        }

        return $input->getOption($name);
    }
}
