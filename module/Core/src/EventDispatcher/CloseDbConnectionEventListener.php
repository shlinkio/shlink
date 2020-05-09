<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\EventDispatcher;

use Shlinkio\Shlink\Common\Doctrine\ReopeningEntityManagerInterface;

class CloseDbConnectionEventListener
{
    private ReopeningEntityManagerInterface $em;
    /** @var callable */
    private $wrapped;

    public function __construct(ReopeningEntityManagerInterface $em, callable $wrapped)
    {
        $this->em = $em;
        $this->wrapped = $wrapped;
    }

    public function __invoke(object $event): void
    {
        $this->em->open();

        try {
            ($this->wrapped)($event);
        } finally {
            $this->em->getConnection()->close();
            $this->em->clear();
        }
    }
}
