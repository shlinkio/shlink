<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230303164233 extends AbstractMigration
{
    private const INDEX_NAME = 'visits_potential_bot_IDX';

    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $this->skipIf($visits->hasIndex(self::INDEX_NAME));

        $visits->dropIndex('IDX_visits_potential_bot'); // Old index
        $visits->addIndex(['potential_bot'], self::INDEX_NAME);
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
