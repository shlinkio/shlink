<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20190930165521 extends AbstractMigration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        if ($shortUrls->hasColumn('domain_id')) {
            return;
        }

        $domains = $schema->createTable('domains');
        $domains->addColumn('id', Types::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $domains->addColumn('authority', Types::STRING, [
            'length' => 512,
            'notnull' => true,
        ]);
        $domains->addUniqueIndex(['authority']);
        $domains->setPrimaryKey(['id']);

        $shortUrls->addColumn('domain_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => false,
        ]);
        $shortUrls->addForeignKeyConstraint('domains', ['domain_id'], ['id'], [
            'onDelete' => 'RESTRICT',
            'onUpdate' => 'RESTRICT',
        ]);
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $schema->getTable('short_urls')->dropColumn('domain_id');
        $schema->dropTable('domains');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
