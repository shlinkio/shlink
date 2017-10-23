<?php
declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171022064541 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function up(Schema $schema)
    {
        $shortUrls = $schema->getTable('short_urls');
        if ($shortUrls->hasColumn('max_visits')) {
            return;
        }

        $shortUrls->addColumn('max_visits', Type::INTEGER, [
            'unsigned' => true,
            'notnull' => false,
        ]);
    }

    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function down(Schema $schema)
    {
        $shortUrls = $schema->getTable('short_urls');
        if (! $shortUrls->hasColumn('max_visits')) {
            return;
        }

        $shortUrls->dropColumn('max_visits');
    }
}
