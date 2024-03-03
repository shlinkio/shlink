<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migrate data from device_long_urls to short_url_redirect_rules
 */
final class Version20240226214216 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf(! $schema->hasTable('device_long_urls'));

        // Insert a rule per every device_long_url, and link it to the corresponding condition
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

            $deviceType = $ruleRow['device_type'];
            $conditionQb = $this->connection->createQueryBuilder();
            $conditionQb->insert('redirect_conditions')
                ->values([
                    'type' => ':type',
                    'match_value' => ':match_value',
                    'match_key' => ':match_key',
                ])
                ->setParameters([
                    'type' => 'device',
                    'match_value' => $deviceType,
                    'match_key' => null,
                ])
                ->executeStatement();
            $conditionId = $this->connection->lastInsertId();

            $relationQb = $this->connection->createQueryBuilder();
            $relationQb->insert('redirect_conditions_in_short_url_redirect_rules')
                ->values([
                    'redirect_condition_id' => ':redirect_condition_id',
                    'short_url_redirect_rule_id' => ':short_url_redirect_rule_id',
                ])
                ->setParameters([
                    'redirect_condition_id' => $conditionId,
                    'short_url_redirect_rule_id' => $ruleId,
                ])
                ->executeStatement();

            $priorities[$shortUrlId] = $priority + 1;
        }
    }
}
