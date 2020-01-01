<?php
namespace PHPSTORM_META;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * PhpStorm Container Interop code completion
 *
 * Add code completion for container-interop.
 *
 * \App\ClassName::class will automatically resolve to it's own name.
 *
 * Custom strings like ``"cache"`` or ``"logger"`` need to be added manually.
 */
$STATIC_METHOD_TYPES = [
    ContainerInterface::get('') => [
        '' == '@',
    ],
    ServiceLocatorInterface::build('') => [
        '' == '@',
    ],
];
