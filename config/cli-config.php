<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

return (static function () {
    /** @var EntityManager $em */
    $em = include __DIR__ . '/entity-manager.php';
    return ConsoleRunner::createHelperSet($em);
})();
