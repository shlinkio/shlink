<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Visit\Repository\VisitDeleterRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\VisitsDeleter;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsDeleterTest extends TestCase
{
    private VisitsDeleter $visitsDeleter;
    private MockObject & VisitDeleterRepositoryInterface $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(VisitDeleterRepositoryInterface::class);
        $this->visitsDeleter = new VisitsDeleter($this->repo);
    }

    #[Test, DataProvider('provideVisitsCounts')]
    public function returnsDeletedVisitsFromRepo(int $visitsCount): void
    {
        $this->repo->expects($this->once())->method('deleteOrphanVisits')->willReturn($visitsCount);

        $result = $this->visitsDeleter->deleteOrphanVisits();

        self::assertEquals($visitsCount, $result->affectedItems);
    }

    public static function provideVisitsCounts(): iterable
    {
        yield '45' => [45];
        yield '5000' => [5000];
        yield '0' => [0];
    }

    #[Test]
    public function returnsNoDeletedVisitsForApiKeyWithNoPermission(): void
    {
        $this->repo->expects($this->never())->method('deleteOrphanVisits');

        $result = $this->visitsDeleter->deleteOrphanVisits(
            ApiKey::fromMeta(ApiKeyMeta::withRoles(RoleDefinition::forNoOrphanVisits())),
        );

        self::assertEquals(0, $result->affectedItems);
    }
}
