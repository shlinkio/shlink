<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Acelaya\ZsmAnnotatedServices\Annotation as DI;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Service\Tag\TagService;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\I18n\Translator\Translator;
use Zend\I18n\Translator\TranslatorInterface;

class ListTagsCommand extends Command
{
    /**
     * @var TagServiceInterface
     */
    private $tagService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ListTagsCommand constructor.
     * @param TagServiceInterface $tagService
     * @param TranslatorInterface $translator
     *
     * @DI\Inject({TagService::class, Translator::class})
     */
    public function __construct(TagServiceInterface $tagService, TranslatorInterface $translator)
    {
        parent::__construct();
        $this->tagService = $tagService;
        $this->translator = $translator;
    }

    protected function configure()
    {
        $this
            ->setName('tag:list')
            ->setDescription('Lists existing tags');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders([$this->translator->translate('Name')])
              ->setRows($this->getTagsRows());

        $table->render();
    }

    private function getTagsRows()
    {
        $tags = $this->tagService->listTags();
        if (empty($tags)) {
            return [[$this->translator->translate('No tags yet')]];
        }

        return array_map(function (Tag $tag) {
            return [$tag->getName()];
        }, $tags);
    }
}
