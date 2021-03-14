<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20210102174433 extends AbstractMigration
{
    private const TABLE_NAME = 'api_key_roles';

    public function up(Schema $schema): void
    {
        $this->skipIf($schema->hasTable(self::TABLE_NAME));

        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('id', Types::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('role_name', Types::STRING, [
            'length' => 255,
            'notnull' => true,
        ]);
        $table->addColumn('meta', Types::JSON, [
            'notnull' => true,
        ]);

        $table->addColumn('api_key_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);
        $table->addForeignKeyConstraint('api_keys', ['api_key_id'], ['id'], [
            'onDelete' => 'CASCADE',
            'onUpdate' => 'RESTRICT',
        ]);
        $table->addUniqueIndex(['role_name', 'api_key_id'], 'UQ_role_plus_api_key');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(! $schema->hasTable(self::TABLE_NAME));
        $schema->getTable(self::TABLE_NAME)->dropIndex('UQ_role_plus_api_key');
        $schema->dropTable(self::TABLE_NAME);
    }
}
