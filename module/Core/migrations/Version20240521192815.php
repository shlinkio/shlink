<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240521192815 extends AbstractMigration
{
    private const INDEX_NAME = 'idx_orig_url_plus_domain';

    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('short_urls');
        $this->skipIf($visits->hasIndex(self::INDEX_NAME));

        $visits->addIndex(['original_url', 'domain_id'], self::INDEX_NAME, [], ['lengths' => [255, null]]);
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
