<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171022064541 extends AbstractMigration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        if ($shortUrls->hasColumn('max_visits')) {
            return;
        }

        $shortUrls->addColumn('max_visits', Types::INTEGER, [
            'unsigned' => true,
            'notnull' => false,
        ]);
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        if (! $shortUrls->hasColumn('max_visits')) {
            return;
        }

        $shortUrls->dropColumn('max_visits');
    }
}
