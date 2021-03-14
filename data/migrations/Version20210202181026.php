<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20210202181026 extends AbstractMigration
{
    private const TITLE = 'title';

    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $this->skipIf($shortUrls->hasColumn(self::TITLE));

        $shortUrls->addColumn(self::TITLE, Types::STRING, [
            'notnull' => false,
            'length' => 512,
        ]);
        $shortUrls->addColumn('title_was_auto_resolved', Types::BOOLEAN, [
            'default' => false,
        ]);
    }

    public function down(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $this->skipIf(! $shortUrls->hasColumn(self::TITLE));
        $shortUrls->dropColumn(self::TITLE);
        $shortUrls->dropColumn('title_was_auto_resolved');
    }
}
