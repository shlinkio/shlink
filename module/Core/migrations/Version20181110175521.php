<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

final class Version20181110175521 extends AbstractMigration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $this->getUserAgentColumn($schema)->setLength(512);
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $this->getUserAgentColumn($schema)->setLength(256);
    }

    /**
     * @throws SchemaException
     */
    private function getUserAgentColumn(Schema $schema): Column
    {
        return $schema->getTable('visits')->getColumn('user_agent');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
