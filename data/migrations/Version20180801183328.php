<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180801183328 extends AbstractMigration
{
    private const NEW_SIZE = 255;
    private const OLD_SIZE = 10;

    /**
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $this->setSize($schema, self::NEW_SIZE);
    }

    /**
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $this->setSize($schema, self::OLD_SIZE);
    }

    /**
     * @throws SchemaException
     */
    private function setSize(Schema $schema, int $size): void
    {
        $schema->getTable('short_urls')->getColumn('short_code')->setLength($size);
    }
}
