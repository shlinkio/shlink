<?php

declare(strict_types=1);

namespace ShlinkMigrations;

use Cake\Chronos\Chronos;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20201102113208 extends AbstractMigration
{
    private const API_KEY_COLUMN = 'author_api_key_id';

    public function up(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $this->skipIf($shortUrls->hasColumn(self::API_KEY_COLUMN));

        $shortUrls->addColumn(self::API_KEY_COLUMN, Types::BIGINT, [
            'unsigned' => true,
            'notnull' => false,
        ]);

        $shortUrls->addForeignKeyConstraint('api_keys', [self::API_KEY_COLUMN], ['id'], [
            'onDelete' => 'SET NULL',
            'onUpdate' => 'RESTRICT',
        ], 'FK_' . self::API_KEY_COLUMN);
    }

    public function postUp(Schema $schema): void
    {
        // If there's only one API key and it's active, link all existing URLs with it
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id')
           ->from('api_keys')
           ->where($qb->expr()->eq('enabled', ':enabled'))
           ->andWhere($qb->expr()->or(
               $qb->expr()->isNull('expiration_date'),
               $qb->expr()->gt('expiration_date', ':expiration'),
           ))
           ->setParameters([
               'enabled' => true,
               'expiration' => Chronos::now()->toDateTimeString(),
           ]);

        /** @var Result $result */
        $result = $qb->execute();
        $id = $this->resolveOneApiKeyId($result);
        if ($id === null) {
            return;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->update('short_urls')
           ->set(self::API_KEY_COLUMN, ':apiKeyId')
           ->setParameter('apiKeyId', $id)
           ->execute();
    }

    /**
     * @return string|int|null
     */
    private function resolveOneApiKeyId(Result $result)
    {
        $results = [];
        while ($row = $result->fetchAssociative()) {
            // As soon as we have to iterate more than once, then we cannot resolve a single API key
            if (! empty($results)) {
                return null;
            }

            $results[] = $row['id'] ?? null;
        }

        return $results[0] ?? null;
    }

    public function down(Schema $schema): void
    {
        $shortUrls = $schema->getTable('short_urls');
        $this->skipIf(! $shortUrls->hasColumn(self::API_KEY_COLUMN));

        $shortUrls->removeForeignKey('FK_' . self::API_KEY_COLUMN);
        $shortUrls->dropColumn(self::API_KEY_COLUMN);
    }
}
