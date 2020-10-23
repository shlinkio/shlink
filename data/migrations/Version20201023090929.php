<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20201023090929 extends AbstractMigration
{
    private const IMPORT_SOURCE_COLUMN = 'import_source';

    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $this->skipIf($shortUrls->hasColumn(self::IMPORT_SOURCE_COLUMN));

        $shortUrls->addColumn(self::IMPORT_SOURCE_COLUMN, Types::STRING, [
            'length' => 255,
            'notnull' => false,
        ]);
    }

    public function down(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $this->skipIf(! $shortUrls->hasColumn(self::IMPORT_SOURCE_COLUMN));

        $shortUrls->dropColumn(self::IMPORT_SOURCE_COLUMN);
    }
}
