<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Paginator\Adapter;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\VisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsPaginatorAdapterTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $repo;

    protected function setUp(): void
    {
        $this->repo = $this->prophesize(VisitRepositoryInterface::class);
    }

    /** @test */
    public function repoIsCalledEveryTimeItemsAreFetched(): void
    {
        $count = 3;
        $limit = 1;
        $offset = 5;
        $adapter = $this->createAdapter(null);
        $findVisits = $this->repo->findVisitsByShortCode('', null, new DateRange(), $limit, $offset, null)->willReturn(
            [],
        );

        for ($i = 0; $i < $count; $i++) {
            $adapter->getSlice($offset, $limit);
        }

        $findVisits->shouldHaveBeenCalledTimes($count);
    }

    /** @test */
    public function repoIsCalledOnlyOnceForCount(): void
    {
        $count = 3;
        $apiKey = ApiKey::create();
        $adapter = $this->createAdapter($apiKey);
        $countVisits = $this->repo->countVisitsByShortCode('', null, new DateRange(), $apiKey->spec())->willReturn(3);

        for ($i = 0; $i < $count; $i++) {
            $adapter->getNbResults();
        }

        $countVisits->shouldHaveBeenCalledOnce();
    }

    private function createAdapter(?ApiKey $apiKey): VisitsPaginatorAdapter
    {
        return new VisitsPaginatorAdapter(
            $this->repo->reveal(),
            new ShortUrlIdentifier(''),
            VisitsParams::fromRawData([]),
            $apiKey !== null ? $apiKey->spec() : null,
        );
    }
}
