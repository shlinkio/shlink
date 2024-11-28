<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

use function in_array;

/**
 * Convert all columns containing long URLs to TEXT type
 */
final class Version20240220214031 extends AbstractMigration
{
    private const DOMAINS_COLUMNS = ['base_url_redirect', 'regular_not_found_redirect', 'invalid_short_url_redirect'];
    private const TEXT_COLUMNS = [
        'domains' => self::DOMAINS_COLUMNS,
        'short_urls' => ['original_url'],
    ];

    public function up(Schema $schema): void
    {
        $textType = Type::getType(Types::TEXT);

        foreach (self::TEXT_COLUMNS as $table => $columns) {
            $t = $schema->getTable($table);

            foreach ($columns as $column) {
                $c = $t->getColumn($column);

                if ($c->getType() === $textType) {
                    continue;
                }

                if (in_array($column, self::DOMAINS_COLUMNS, true)) {
                    // Domain columns had an incorrect length
                    $t->modifyColumn($column, ['length' => 2048]);
                }
                $c->setType($textType);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Can't revert from TEXT to STRING, as it's bigger
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
