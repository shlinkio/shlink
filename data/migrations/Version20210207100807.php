<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Visitor;

final class Version20210207100807 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $shortUrlId = $visits->getColumn('short_url_id');

        $this->skipIf(! $shortUrlId->getNotnull());

        $shortUrlId->setNotnull(false);

        $visits->addColumn('visited_url', Types::STRING, [
            'length' => Visitor::VISITED_URL_MAX_LENGTH,
            'notnull' => false,
        ]);
        $visits->addColumn('type', Types::STRING, [
            'length' => 255,
            'default' => Visit::TYPE_VALID_SHORT_URL,
        ]);
    }

    public function down(Schema $schema): void
    {
        $visits = $schema->getTable('visits');
        $shortUrlId = $visits->getColumn('short_url_id');

        $this->skipIf($shortUrlId->getNotnull());

        $shortUrlId->setNotnull(true);
        $visits->dropColumn('visited_url');
        $visits->dropColumn('type');
    }

    /**
     * @fixme Workaround for https://github.com/doctrine/migrations/issues/1104
     */
    public function isTransactional(): bool
    {
        return false;
    }
}
