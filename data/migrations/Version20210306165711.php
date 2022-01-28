<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20210306165711 extends AbstractMigration
{
    private const TABLE = 'api_keys';
    private const COLUMN = 'name';

    public function up(Schema $schema): void
    {
        $apiKeys = $schema->getTable(self::TABLE);
        $this->skipIf($apiKeys->hasColumn(self::COLUMN));

        $apiKeys->addColumn(
            self::COLUMN,
            Types::STRING,
            [
                'notnull' => false,
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $apiKeys = $schema->getTable(self::TABLE);
        $this->skipIf(! $apiKeys->hasColumn(self::COLUMN));

        $apiKeys->dropColumn(self::COLUMN);
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
