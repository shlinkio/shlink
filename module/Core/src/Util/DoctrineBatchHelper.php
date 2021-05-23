<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Doctrine\ORM\EntityManagerInterface;
use Throwable;

/**
 * Inspired by ocramius/doctrine-batch-utils https://github.com/Ocramius/DoctrineBatchUtils
 */
class DoctrineBatchHelper implements DoctrineBatchHelperInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @throws Throwable
     */
    public function wrapIterable(iterable $resultSet, int $batchSize): iterable
    {
        $iteration = 0;

        $this->em->beginTransaction();

        try {
            foreach ($resultSet as $key => $value) {
                $iteration++;
                yield $key => $value;
                $this->flushAndClearBatch($iteration, $batchSize);
            }
        } catch (Throwable $e) {
            $this->em->rollback();

            throw $e;
        }

        $this->flushAndClearEntityManager();
        $this->em->commit();
    }

    private function flushAndClearBatch(int $iteration, int $batchSize): void
    {
        if ($iteration % $batchSize) {
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
