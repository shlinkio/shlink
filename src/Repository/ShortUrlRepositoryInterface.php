<?php
namespace Acelaya\UrlShortener\Repository;

use Doctrine\Common\Persistence\ObjectRepository;

interface ShortUrlRepositoryInterface extends ObjectRepository, PaginableRepository
{
}
