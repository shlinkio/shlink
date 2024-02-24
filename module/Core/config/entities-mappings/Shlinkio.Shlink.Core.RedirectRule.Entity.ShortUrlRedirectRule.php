<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

return static function (ClassMetadata $metadata, array $emConfig): void {
    $builder = new ClassMetadataBuilder($metadata);

    $builder->setTable(determineTableName('short_url_redirect_rules', $emConfig));

    $builder->createField('id', Types::BIGINT)
            ->columnName('id')
            ->makePrimaryKey()
            ->generatedValue('IDENTITY')
            ->option('unsigned', true)
            ->build();

    $builder->createField('priority', Types::INTEGER)
            ->columnName('priority')
            ->build();

    fieldWithUtf8Charset($builder->createField('longUrl', Types::TEXT), $emConfig)
        ->columnName('long_url')
        ->length(2048)
        ->build();

    $builder->createManyToOne('shortUrl', ShortUrl\Entity\ShortUrl::class)
            ->addJoinColumn('short_url_id', 'id', nullable: false, onDelete: 'CASCADE')
            ->build();

    $builder->createManyToMany('conditions', RedirectRule\Entity\RedirectCondition::class)
            ->setJoinTable(determineTableName('redirect_conditions_in_short_url_redirect_rules', $emConfig))
            ->addInverseJoinColumn('redirect_condition_id', 'id', onDelete: 'CASCADE')
            ->addJoinColumn('short_url_redirect_rule_id', 'id', onDelete: 'CASCADE')
            ->fetchEager() // Always fetch the corresponding conditions when loading a rule
            ->build();
};
