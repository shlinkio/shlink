<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20210522124633 extends AbstractMigration
{
    private const POTENTIAL_BOT_COLUMN = 'potential_bot';

    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $this->skipIf($visits->hasColumn(self::POTENTIAL_BOT_COLUMN));
        $visits->addColumn(self::POTENTIAL_BOT_COLUMN, Types::BOOLEAN, ['default' => false]);
    }

    public function down(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $this->skipIf(! $visits->hasColumn(self::POTENTIAL_BOT_COLUMN));
        $visits->dropColumn(self::POTENTIAL_BOT_COLUMN);
    }

    public function isTransactional(): bool
    {
        return ! ($this->connection->getDatabasePlatform() instanceof MySQLPlatform);
    }
}
