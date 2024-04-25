<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240425194703 extends AbstractMigration
{
    private const INDEX_NAME = 'IDX_links_expiry_date';

    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('short_urls');
        $this->skipIf($visits->hasIndex(self::INDEX_NAME));

        $visits->addIndex(['valid_until'], self::INDEX_NAME);
    }

    public function down(Schema $schema): void
    {
        $visits = $schema->getTable('short_urls');
        $this->skipIf(!$visits->hasIndex(self::INDEX_NAME));

        $visits->dropIndex(self::INDEX_NAME);
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
