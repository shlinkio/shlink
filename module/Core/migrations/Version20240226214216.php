<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240226214216 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf(! $schema->hasTable('device_long_urls'));

        // First create redirect conditions for all device types
        $qb = $this->connection->createQueryBuilder();
        $devices = $qb->select('device_type')
                      ->distinct()
                      ->from('device_long_urls')
                      ->executeQuery();

        $conditionIds = [];
        while ($deviceRow = $devices->fetchAssociative()) {
            $deviceType = $deviceRow['device_type'];
            $conditionQb = $this->connection->createQueryBuilder();
            $conditionQb->insert('redirect_conditions')
                ->values([
                    'name' => ':name',
                    'type' => ':type',
                    'match_value' => ':match_value',
                    'match_key' => ':match_key',
                ])
                ->setParameters([
                    'name' => 'device-' . $deviceType,
                    'type' => 'device',
                    'match_value' => $deviceType,
                    'match_key' => null,
                ])
                ->executeStatement();
            $id = $this->connection->lastInsertId();
            $conditionIds[$deviceType] = $id;
        }

        // Then insert a rule per every device_long_url, and link it to the corresponding condition
        $qb = $this->connection->createQueryBuilder();
        $rules = $qb->select('short_url_id', 'device_type', 'long_url')
                    ->from('device_long_urls')
                    ->executeQuery();

        $priorities = [];
        while ($ruleRow = $rules->fetchAssociative()) {
            $shortUrlId = $ruleRow['short_url_id'];
            $priority = $priorities[$shortUrlId] ?? 1;

            $ruleQb = $this->connection->createQueryBuilder();
            $ruleQb->insert('short_url_redirect_rules')
                ->values([
                    'priority' => ':priority',
                    'long_url' => ':long_url',
                    'short_url_id' => ':short_url_id',
                ])
                ->setParameters([
                    'priority' => $priority,
                    'long_url' => $ruleRow['long_url'],
                    'short_url_id' => $shortUrlId,
                ])
                ->executeStatement();
            $ruleId = $this->connection->lastInsertId();

            $relationQb = $this->connection->createQueryBuilder();
            $relationQb->insert('redirect_conditions_in_short_url_redirect_rules')
                ->values([
                    'redirect_condition_id' => ':redirect_condition_id',
                    'short_url_redirect_rule_id' => ':short_url_redirect_rule_id',
                ])
                ->setParameters([
                    'redirect_condition_id' => $conditionIds[$ruleRow['device_type']],
                    'short_url_redirect_rule_id' => $ruleId,
                ])
                ->executeStatement();

            $priorities[$shortUrlId] = $priority + 1;
        }
    }

    public function postUp(Schema $schema): void
    {
        $this->skipIf(! $schema->hasTable('device_long_urls'));
        $schema->dropTable('device_long_urls');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf($schema->hasTable('device_long_urls'));

        $table = $schema->createTable('device_long_urls');
        $table->addColumn('id', Types::BIGINT, [
            'unsigned' => true,
            'autoincrement' => true,
            'notnull' => true,
        ]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('device_type', Types::STRING, ['length' => 255]);
        $table->addColumn('long_url', Types::TEXT, ['length' => 2048]);
        $table->addColumn('short_url_id', Types::BIGINT, [
            'unsigned' => true,
            'notnull' => true,
        ]);

        $table->addForeignKeyConstraint('short_urls', ['short_url_id'], ['id'], [
            'onDelete' => 'CASCADE',
            'onUpdate' => 'RESTRICT',
        ]);

        $table->addUniqueIndex(['device_type', 'short_url_id'], 'UQ_device_type_per_short_url');
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
