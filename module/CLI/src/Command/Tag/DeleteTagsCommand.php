<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Tag;

use Shlinkio\Shlink\Core\Service\Tag\TagServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\I18n\Translator\TranslatorInterface;

class DeleteTagsCommand extends Command
{
    public const NAME = 'tag:delete';

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

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription($this->translator->translate('Deletes one or more tags.'))
            ->addOption(
                'name',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                $this->translator->translate('The name of the tags to delete')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $tagNames = $input->getOption('name');

        if (empty($tagNames)) {
            $io->warning($this->translator->translate('You have to provide at least one tag name'));
            return;
        }

        $this->tagService->deleteTags($tagNames);
        $io->success($this->translator->translate('Tags properly deleted'));
    }
}
