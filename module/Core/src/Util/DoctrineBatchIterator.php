<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Doctrine\ORM\EntityManagerInterface;
use IteratorAggregate;
use Throwable;

/**
 * Inspired by ocramius/doctrine-batch-utils https://github.com/Ocramius/DoctrineBatchUtils
 */
class DoctrineBatchIterator implements IteratorAggregate
{
    private iterable $resultSet;
    private EntityManagerInterface $em;
    private int $batchSize;

    public function __construct(iterable $resultSet, EntityManagerInterface $em, int $batchSize)
    {
        $this->resultSet = $resultSet;
        $this->em = $em;
        $this->batchSize = $batchSize;
    }

    /**
     * @throws Throwable
     */
    public function getIterator(): iterable
    {
        $iteration = 0;
        $resultSet = $this->resultSet;

        $this->em->beginTransaction();

        try {
            foreach ($resultSet as $key => $value) {
                $iteration++;
                yield $key => $value;
                $this->flushAndClearBatch($iteration);
            }
        } catch (Throwable $e) {
            $this->em->rollback();

            throw $e;
        }

        $this->flushAndClearEntityManager();
        $this->em->commit();
    }

    private function flushAndClearBatch(int $iteration): void
    {
        if ($iteration % $this->batchSize) {
            return;
        }

        $this->flushAndClearEntityManager();
    }

    private function flushAndClearEntityManager(): void
    {
        $this->em->flush();
        $this->em->clear();
    }
}
