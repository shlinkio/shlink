<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180915110857 extends AbstractMigration
{
    private const ON_DELETE_MAP = [
        'visit_locations' => 'SET NULL',
        'short_urls' => 'CASCADE',
    ];

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $foreignKeys = $visits->getForeignKeys();

        // Remove all existing foreign keys and add them again with CASCADE delete
        foreach ($foreignKeys as $foreignKey) {
            $visits->removeForeignKey($foreignKey->getName());
            $foreignTable = $foreignKey->getForeignTableName();

            $visits->addForeignKeyConstraint(
                $foreignTable,
                $foreignKey->getLocalColumns(),
                $foreignKey->getForeignColumns(),
                [
                    'onDelete' => self::ON_DELETE_MAP[$foreignTable],
                    'onUpdate' => 'RESTRICT',
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Nothing to run
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
