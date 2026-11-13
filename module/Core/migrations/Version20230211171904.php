<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

final class Version20230211171904 extends AbstractMigration
{
    private const string INDEX_NAME = 'IDX_visits_potential_bot';

    #[Override]
    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $this->skipIf($visits->hasIndex(self::INDEX_NAME));

        $visits->addIndex(['short_url_id', 'potential_bot'], self::INDEX_NAME);
    }

    #[Override]
    public function isTransactional(): bool
    {
        return !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform;
    }
}
