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
class Version20171021093246 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function up(Schema $schema)
    {
        $shortUrls = $schema->getTable('short_urls');
        $shortUrls->addColumn('valid_since', Type::DATETIME, [
            'notnull' => false,
        ]);
        $shortUrls->addColumn('valid_until', Type::DATETIME, [
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
        $shortUrls->dropColumn('valid_since');
        $shortUrls->dropColumn('valid_until');
    }
}
