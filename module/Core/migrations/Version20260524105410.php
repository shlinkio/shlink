<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates a new long_url_hash column, which is temporarily empty and nullable
 */
final class Version20260524105410 extends AbstractMigration
{
    private const string COLUMN_NAME = 'long_url_hash';
    private const string INDEX_NAME = 'IDX_' . self::COLUMN_NAME;

    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        if ($shortUrls->hasColumn(self::COLUMN_NAME)) {
            return;
        }

        if ($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
            $this->addSql('ALTER TABLE short_urls ADD ' . self::COLUMN_NAME . ' VARBINARY(32) NOT NULL DEFAULT (0x00)');
            $this->addSql('CREATE INDEX ' . self::INDEX_NAME . ' ON short_urls(' . self::COLUMN_NAME . ')');
        } else {
            $shortUrls->addColumn(self::COLUMN_NAME, Types::BINARY, [
                'length' => 32,
                // Temporary default value until the column can be filled by the next migration
                'default' => '',
            ]);
            $shortUrls->addIndex([self::COLUMN_NAME], self::INDEX_NAME);
        }
    }

    public function down(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $this->skipIf(!$shortUrls->hasColumn(self::COLUMN_NAME));

        $shortUrls->dropIndex(self::INDEX_NAME);
        $shortUrls->dropColumn(self::COLUMN_NAME);
    }

    public function isTransactional(): bool
    {
        return !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform;
    }
}
