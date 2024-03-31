<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('short_url_visits_counts', $emConfig))
            ->setCustomRepositoryClass(Visit\Repository\ShortUrlVisitsCountRepository::class);

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    $builder->createField('potentialBot', Types::BOOLEAN)
            ->columnName('potential_bot')
            ->option('default', false)
            ->build();

    $builder->createField('count', Types::BIGINT)
            ->columnName('count')
            ->option('unsigned', true)
            ->option('default', 1)
            ->build();

    $builder->createField('slotId', Types::INTEGER)
            ->columnName('slot_id')
            ->option('unsigned', true)
            ->build();

    $builder->createManyToOne('shortUrl', ShortUrl\Entity\ShortUrl::class)
            ->addJoinColumn('short_url_id', 'id', onDelete: 'CASCADE')
            ->build();

    $builder->addUniqueConstraint(['short_url_id', 'potential_bot', 'slot_id'], 'UQ_slot_per_short_url');
};
