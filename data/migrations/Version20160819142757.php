<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

use function is_subclass_of;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160819142757 extends AbstractMigration
{
    /**
     * @throws Exception
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $platformClass = $this->connection->getDatabasePlatform();
        $table = $schema->getTable('short_urls');
        $column = $table->getColumn('short_code');

        match (true) {
            is_subclass_of($platformClass, MySQLPlatform::class) => $column
                ->setPlatformOption('charset', 'utf8mb4')
                ->setPlatformOption('collation', 'utf8mb4_bin'),
            is_subclass_of($platformClass, SqlitePlatform::class) => $column->setPlatformOption('collate', 'BINARY'),
            default => null,
        };
    }

    public function down(Schema $schema): void
    {
        // Nothing to roll back
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
