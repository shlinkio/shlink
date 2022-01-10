<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220110113313 extends AbstractMigration
{
    private const CHARSET = 'utf8mb4';
    private const COLLATIONS = [
        'short_urls' => [
            'original_url' => 'unicode_ci',
            'short_code' => 'bin',
            'import_original_short_code' => 'unicode_ci',
            'title' => 'unicode_ci',
        ],
        'domains' => [
            'authority' => 'unicode_ci',
            'base_url_redirect' => 'unicode_ci',
            'regular_not_found_redirect' => 'unicode_ci',
            'invalid_short_url_redirect' => 'unicode_ci',
        ],
        'tags' => [
            'name' => 'unicode_ci',
        ],
        'visits' => [
            'referer' => 'unicode_ci',
            'user_agent' => 'unicode_ci',
            'visited_url' => 'unicode_ci',
        ],
        'visit_locations' => [
            'country_code' => 'unicode_ci',
            'country_name' => 'unicode_ci',
            'region_name' => 'unicode_ci',
            'city_name' => 'unicode_ci',
            'timezone' => 'unicode_ci',
        ],
    ];

    public function up(Schema $schema): void
    {
        $this->skipIf(! $this->isMySql(), 'This only sets MySQL-specific database options');

        foreach (self::COLLATIONS as $tableName => $columns) {
            $table = $schema->getTable($tableName);

            foreach ($columns as $columnName => $collation) {
                $table->getColumn($columnName)
                      ->setPlatformOption('charset', self::CHARSET)
                      ->setPlatformOption('collation', self::CHARSET . '_' . $collation);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // No down
    }

    public function isTransactional(): bool
    {
        return ! $this->isMySql();
    }

    private function isMySql(): bool
    {
        return $this->connection->getDatabasePlatform() instanceof MySQLPlatform;
    }
}
