<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

final class Version20190824075137 extends AbstractMigration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $this->getRefererColumn($schema)->setLength(1024);
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $this->getRefererColumn($schema)->setLength(256);
    }

    /**
     * @throws SchemaException
     */
    private function getRefererColumn(Schema $schema): Column
    {
        return $schema->getTable('visits')->getColumn('referer');
    }
}
