<?php
namespace Shlinkio\Shlink\Common\Twig\Extension;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Zend\I18n\Translator\TranslatorInterface;

class TranslatorExtension extends \Twig_Extension implements TranslatorInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * TranslatorExtension constructor.
     * @param TranslatorInterface $translator
     *
     * @Inject({"translator"})
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return __CLASS__;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('translate', [$this, 'translate']),
            new \Twig_SimpleFunction('translate_plural', [$this, 'translatePlural']),
        ];
    }

    /**
     * Translate a message.
     *
     * @param  string $message
     * @param  string $textDomain
     * @param  string $locale
     * @return string
     */
    public function translate($message, $textDomain = 'default', $locale = null)
    {
        return $this->translator->translate($message, $textDomain, $locale);
    }

    /**
     * Translate a plural message.
     *
     * @param  string $singular
     * @param  string $plural
     * @param  int $number
     * @param  string $textDomain
     * @param  string|null $locale
     * @return string
     */
    public function translatePlural(
        $singular,
        $plural,
        $number,
        $textDomain = 'default',
        $locale = null
    ) {
        $this->translator->translatePlural($singular, $plural, $number, $textDomain, $locale);
    }
}
