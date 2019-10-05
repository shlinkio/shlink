<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171021093246 extends AbstractMigration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        if ($shortUrls->hasColumn('valid_since')) {
            return;
        }

        $shortUrls->addColumn('valid_since', Type::DATETIME, [
            'notnull' => false,
        ]);
        $shortUrls->addColumn('valid_until', Type::DATETIME, [
            'notnull' => false,
        ]);
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        if (! $shortUrls->hasColumn('valid_since')) {
            return;
        }

        $shortUrls->dropColumn('valid_since');
        $shortUrls->dropColumn('valid_until');
    }
}
