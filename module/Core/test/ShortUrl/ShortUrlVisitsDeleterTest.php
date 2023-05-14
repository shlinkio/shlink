<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlVisitsDeleter;
use Shlinkio\Shlink\Core\Visit\Repository\VisitDeleterRepositoryInterface;

class ShortUrlVisitsDeleterTest extends TestCase
{
    private ShortUrlVisitsDeleter $deleter;
    private MockObject & VisitDeleterRepositoryInterface $repository;
    private MockObject & ShortUrlResolverInterface $resolver;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VisitDeleterRepositoryInterface::class);
        $this->resolver = $this->createMock(ShortUrlResolverInterface::class);

        $this->deleter = new ShortUrlVisitsDeleter($this->repository, $this->resolver);
    }

    #[Test, DataProvider('provideVisitsCounts')]
    public function returnsDeletedVisitsFromRepo(int $visitsCount): void
    {
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain('');
        $shortUrl = ShortUrl::withLongUrl('https://example.com');

        $this->resolver->expects($this->once())->method('resolveShortUrl')->with($identifier, null)->willReturn(
            $shortUrl,
        );
        $this->repository->expects($this->once())->method('deleteShortUrlVisits')->with($shortUrl)->willReturn(
            $visitsCount,
        );

        $result = $this->deleter->deleteShortUrlVisits($identifier, null);

        self::assertEquals($visitsCount, $result->affectedItems);
    }

    public static function provideVisitsCounts(): iterable
    {
        yield '45' => [45];
        yield '5000' => [5000];
        yield '0' => [0];
    }
}
