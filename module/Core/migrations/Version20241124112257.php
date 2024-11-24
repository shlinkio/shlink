<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20241124112257 extends AbstractMigration
{
    private const COLUMN_NAME = 'redirect_url';

    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $this->skipIf($visits->hasColumn(self::COLUMN_NAME));

        $visits->addColumn('redirected_url', Types::STRING, [
            'length' => 2048,
            'notnull' => false,
            'default' => null,
        ]);
    }

    public function down(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $this->skipIf(! $visits->hasColumn(self::COLUMN_NAME));
        $visits->dropColumn(self::COLUMN_NAME);
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
