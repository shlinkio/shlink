<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

final class Version20191020074522 extends AbstractMigration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $this->getOriginalUrlColumn($schema)->setLength(2048);
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $this->getOriginalUrlColumn($schema)->setLength(1024);
    }

    /**
     * @throws SchemaException
     */
    private function getOriginalUrlColumn(Schema $schema): Column
    {
        return $schema->getTable('short_urls')->getColumn('original_url');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
