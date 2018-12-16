<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

/** @var $metadata ClassMetadata */
$builder = new ClassMetadataBuilder($metadata);

$builder->setTable('tags')
        ->setCustomRepositoryClass(Repository\TagRepository::class);

$builder->createField('id', Type::BIGINT)
        ->columnName('id')
        ->makePrimaryKey()
        ->generatedValue('IDENTITY')
        ->option('unsigned', true)
        ->build();

$builder->createField('name', Type::STRING)
        ->unique()
        ->build();
