<?php


namespace Core\Internationalization;


class I18nNode
{
    public array $children = [];
    public array $values = [];

    public function __construct(?\SimpleXMLElement $xml = null)
    {
        if ($xml !== null) {
            foreach ($xml->node as $node) {
                $this->addChild($node->attributes()->name, new I18nNode($node));
            }
            foreach ($xml->value as $value) {
                $this->addValue($value->attributes()->lang, new I18nTextValue($value));
            }
        }
    }

    public function addChild(string $name, I18nNode $child)
    {
        $this->children[$name] = $child;
    }

    private function addValue(string $lang, I18nValue $param)
    {
        $this->values[$lang] = $param;
    }

    public function getChild(string $name): I18nNode
    {
        if (isset($this->children[$name]))
            return $this->children[$name];
        else
            throw new I18nNodeNotFoundException();
    }

    public function getValue(LanguagesHierarchy $languageHierarchy): I18nValue
    {
        $bestLang = $languageHierarchy->pickBest(array_keys($this->values));
        return $this->values[$bestLang];
    }
}