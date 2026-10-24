<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\ShortUrl\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Exception as PdoDriverException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlMode;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;

class ShortUrlRepositoryErrorHandlingTest extends TestCase
{
    /** Query-boundary contracts complement the real-driver transaction and error-propagation tests. */
    #[Test, DataProvider('provideDriverErrors')]
    public function onlyPostgresEncodingErrorsBecomeMissingShortUrls(
        bool $isPostgres,
        bool $activeTransaction,
        string $sqlState,
    ): void {
        $pdoException = new PDOException('Rejected query parameter');
        $pdoException->errorInfo = [$sqlState, 7];
        $exception = new DriverException(PdoDriverException::new($pdoException), null);

        $query = $this->createMock(Query::class);
        $query->method('setParameters')->willReturnSelf();
        $query->method('setFirstResult')->willReturnSelf();
        $query->method('setMaxResults')->willReturnSelf();
        $query->expects(self::once())->method('getOneOrNullResult')->willThrowException($exception);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn(
                $isPostgres ? new PostgreSQLPlatform() : new SQLitePlatform(),
            );
        $connection->method('isTransactionActive')->willReturn($activeTransaction);
        $connection
            ->expects($isPostgres && $activeTransaction ? self::once() : self::never())
            ->method('transactional')
            ->willReturnCallback(static fn (callable $operation) => $operation($connection));

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getExpressionBuilder')->willReturn(new Expr());
        $em->method('createQueryBuilder')->willReturn(new QueryBuilder($em));
        $em->method('createQuery')->willReturn($query);
        $repo = new ShortUrlRepository($em, new ClassMetadata(ShortUrl::class));

        if (!$isPostgres || $sqlState !== '22021') {
            $this->expectExceptionObject($exception);
        }

        self::assertNull($repo->findOneWithDomainFallback(
            ShortUrlIdentifier::fromShortCodeAndDomain("\xC0"),
            ShortUrlMode::STRICT,
        ));
    }

    /** @return iterable<string, array{bool, bool, string}> */
    public static function provideDriverErrors(): iterable
    {
        yield 'postgres encoding, autocommit' => [true, false, '22021'];
        yield 'postgres encoding, transaction' => [true, true, '22021'];
        yield 'postgres schema, autocommit' => [true, false, '42P01'];
        yield 'postgres schema, transaction' => [true, true, '42P01'];
        yield 'other platform encoding, autocommit' => [false, false, '22021'];
        yield 'other platform encoding, transaction' => [false, true, '22021'];
    }
}
