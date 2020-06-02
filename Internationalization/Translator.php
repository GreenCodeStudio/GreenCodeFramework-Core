<?php


namespace Core\Internationalization;


class Translator
{
    public static Translator $default;
    private LanguagesHierarchy $languageHierarchy;

    public function __construct(LanguagesHierarchy $languagesHierarchy)
    {
        $this->languageHierarchy = $languagesHierarchy;
    }

    /**
     * @throws I18nNodeNotFoundException
     */
    public function translate(string $q): I18nValue
    {
        $path = explode('.', $q);
        $node = TextsRepository::getRootNode();
        foreach ($path as $nodeName) {
            $node = $node->getChild($nodeName);
        }
        return $node->getValue($this->languageHierarchy);
    }
}

Translator::$default = new Translator(new LanguagesHierarchy());