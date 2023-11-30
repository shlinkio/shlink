<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200110182849 extends AbstractMigration
{
    private const DEFAULT_EMPTY_VALUE = '';
    private const COLUMN_DEFAULTS_MAP = [
        'visits' => [
            'referer',
            'user_agent',
        ],
        'visit_locations' => [
            'timezone',
            'country_code',
            'country_name',
            'region_name',
            'city_name',
        ],
    ];

    public function up(Schema $schema): void
    {
        foreach (self::COLUMN_DEFAULTS_MAP as $tableName => $columns) {
            foreach ($columns as $columnName) {
                $this->setDefaultValueForColumnInTable($tableName, $columnName);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function setDefaultValueForColumnInTable(string $tableName, string $columnName): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->update($tableName)
           ->set($columnName, ':emptyValue')
           ->setParameter('emptyValue', self::DEFAULT_EMPTY_VALUE)
           ->where($qb->expr()->isNull($columnName))
           ->executeStatement();
    }

    public function down(Schema $schema): void
    {
        // No need (and no way) to undo this migration
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
