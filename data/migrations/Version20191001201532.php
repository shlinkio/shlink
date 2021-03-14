<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

use function array_reduce;

final class Version20191001201532 extends AbstractMigration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        if ($shortUrls->hasIndex('unique_short_code_plus_domain')) {
            return;
        }

        /** @var Index|null $shortCodesIndex */
        $shortCodesIndex = array_reduce($shortUrls->getIndexes(), function (?Index $found, Index $current) {
            [$column] = $current->getColumns();
            return $column === 'short_code' ? $current : $found;
        });
        if ($shortCodesIndex === null) {
            return;
        }

        $shortUrls->dropIndex($shortCodesIndex->getName());
        $shortUrls->addUniqueIndex(['short_code', 'domain_id'], 'unique_short_code_plus_domain');
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');

        $shortUrls->dropIndex('unique_short_code_plus_domain');
        $shortUrls->addUniqueIndex(['short_code']);
    }
}
