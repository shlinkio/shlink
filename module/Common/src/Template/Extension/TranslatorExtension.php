<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Template\Extension;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use Zend\I18n\Translator\TranslatorInterface;

class TranslatorExtension implements ExtensionInterface
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('translate', [$this->translator, 'translate']);
    }
}
