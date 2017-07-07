<?php
namespace Shlinkio\Shlink\Core\Service\Tag;

use Acelaya\ZsmAnnotatedServices\Annotation as DI;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Tag;

class TagService implements TagServiceInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * VisitService constructor.
     * @param EntityManagerInterface $em
     *
     * @DI\Inject({"em"})
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return Tag[]
     * @throws \UnexpectedValueException
     */
    public function listTags()
    {
        return $this->em->getRepository(Tag::class)->findBy([], ['name' => 'DESC']);
    }
}
