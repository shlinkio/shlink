<?php
namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

    public function __construct(TagServiceInterface $tagService, TranslatorInterface $translator)
    {
        $this->tagService = $tagService;
        $this->translator = $translator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('tag:list')
            ->setDescription($this->translator->translate('Lists existing tags.'));
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
