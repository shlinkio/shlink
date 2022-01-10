<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200503170404 extends AbstractMigration
{
    private const INDEX_NAME = 'IDX_visits_date';

    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $this->skipIf($visits->hasIndex(self::INDEX_NAME));
        $visits->addIndex(['date'], self::INDEX_NAME);
    }

    public function down(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $this->skipIf(! $visits->hasIndex(self::INDEX_NAME));
        $visits->dropIndex(self::INDEX_NAME);
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
