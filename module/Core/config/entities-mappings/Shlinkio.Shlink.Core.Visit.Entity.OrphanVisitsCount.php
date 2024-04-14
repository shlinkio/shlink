<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('orphan_visits_counts', $emConfig))
            ->setCustomRepositoryClass(Visit\Repository\OrphanVisitsCountRepository::class);

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

    $builder->addUniqueConstraint(['potential_bot', 'slot_id'], 'UQ_slot');
};
