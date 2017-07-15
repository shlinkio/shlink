<?php
namespace Shlinkio\Shlink\Core\Service\Tag;

use Acelaya\ZsmAnnotatedServices\Annotation as DI;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;

class TagService implements TagServiceInterface
{
    use TagManagerTrait;

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
        return $this->em->getRepository(Tag::class)->findBy([], ['name' => 'ASC']);
    }

    /**
     * @param array $tagNames
     * @return void
     */
    public function deleteTags(array $tagNames)
    {
        /** @var TagRepository $repo */
        $repo = $this->em->getRepository(Tag::class);
        $repo->deleteByName($tagNames);
    }

    /**
     * Provided a list of tag names, creates all that do not exist yet
     *
     * @param string[] $tagNames
     * @return Collection|Tag[]
     */
    public function createTags(array $tagNames)
    {
        $tags = $this->tagNamesToEntities($this->em, $tagNames);
        $this->em->flush();

        return $tags;
    }
}
