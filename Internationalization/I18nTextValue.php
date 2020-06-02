<?php


namespace Core\Internationalization;


class I18nTextValue extends I18nValue
{
    private string $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}