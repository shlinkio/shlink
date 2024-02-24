<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240224115725 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf($schema->hasTable('short_url_redirect_rules'), 'New columns already exist');

        $redirectRules = $this->createTableWithId($schema, 'short_url_redirect_rules');
        $redirectRules->addColumn('priority', Types::INTEGER, ['unsigned' => true, 'default' => 1]);
        // The length here is just so that Doctrine knows it should not use too small text types
        $redirectRules->addColumn('long_url', Types::TEXT, ['length' => 2048]);

        $redirectRules->addColumn('short_url_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);
        $redirectRules->addForeignKeyConstraint('short_urls', ['short_url_id'], ['id'], [
            'onDelete' => 'CASCADE',
            'onUpdate' => 'RESTRICT',
        ]);

        $redirectConditions = $this->createTableWithId($schema, 'redirect_conditions');
        $redirectConditions->addColumn('name', Types::STRING, ['length' => 512]);
        $redirectConditions->addUniqueIndex(['name'], 'UQ_name');

        $redirectConditions->addColumn('type', Types::STRING, ['length' => 255]);
        $redirectConditions->addColumn('match_key', Types::STRING, [
            'length' => 512,
            'notnull' => false,
            'default' => null,
        ]);
        $redirectConditions->addColumn('match_value', Types::STRING, ['length' => 512]);

        $joinTable = $schema->createTable('redirect_conditions_in_short_url_redirect_rules');

        $joinTable->addColumn('redirect_condition_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);
        $joinTable->addForeignKeyConstraint('redirect_conditions', ['redirect_condition_id'], ['id'], [
            'onDelete' => 'CASCADE',
            'onUpdate' => 'RESTRICT',
        ]);

        $joinTable->addColumn('short_url_redirect_rule_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);
        $joinTable->addForeignKeyConstraint('short_url_redirect_rules', ['short_url_redirect_rule_id'], ['id'], [
            'onDelete' => 'CASCADE',
            'onUpdate' => 'RESTRICT',
        ]);

        $joinTable->setPrimaryKey(['redirect_condition_id', 'short_url_redirect_rule_id']);
    }

    private function createTableWithId(Schema $schema, string $tableName): Table
    {
        $table = $schema->createTable($tableName);
        $table->addColumn('id', Types::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $table->setPrimaryKey(['id']);

        return $table;
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(! $schema->hasTable('short_url_redirect_rules'), 'Columns do not exist');

        $schema->dropTable('redirect_conditions_in_short_url_redirect_rules');
        $schema->dropTable('short_url_redirect_rules');
        $schema->dropTable('redirect_conditions');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
