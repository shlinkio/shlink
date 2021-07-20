<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

use function method_exists;
use function Shlinkio\Shlink\Core\kebabCaseToCamelCase;
use function sprintf;
use function str_contains;

/** @deprecated */
abstract class BaseCommand extends Command
{
    /**
     * @param string|string[]|bool|null $default
     */
    protected function addOptionWithDeprecatedFallback(
        string $name,
        ?string $shortcut = null,
        ?int $mode = null,
        string $description = '',
        bool|string|array|null $default = null,
    ): self {
        $this->addOption($name, $shortcut, $mode, $description, $default);

        if (str_contains($name, '-')) {
            $camelCaseName = kebabCaseToCamelCase($name);
            $this->addOption($camelCaseName, null, $mode, sprintf('[DEPRECATED] Alias for "%s".', $name), $default);
        }

        return $this;
    }

    // @phpstan-ignore-next-line
    protected function getOptionWithDeprecatedFallback(InputInterface $input, string $name) // phpcs:ignore
    {
        $rawInput = method_exists($input, '__toString') ? $input->__toString() : '';
        $camelCaseName = kebabCaseToCamelCase($name);
        $resolvedOptionName = str_contains($rawInput, $camelCaseName) ? $camelCaseName : $name;

        return $input->getOption($resolvedOptionName);
    }
}
