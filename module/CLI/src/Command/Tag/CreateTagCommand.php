<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\I18n\Translator\TranslatorInterface;

class CreateTagCommand extends Command
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
            ->setName('tag:create')
            ->setDescription($this->translator->translate('Creates one or more tags.'))
            ->addOption(
                'name',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                $this->translator->translate('The name of the tags to create')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tagNames = $input->getOption('name');
        if (empty($tagNames)) {
            $output->writeln(sprintf(
                '<comment>%s</comment>',
                $this->translator->translate('You have to provide at least one tag name')
            ));
            return;
        }

        $this->tagService->createTags($tagNames);
        $output->writeln($this->translator->translate('Created tags') . sprintf(': ["<info>%s</info>"]', implode(
            '</info>", "<info>',
            $tagNames
        )));
    }
}
