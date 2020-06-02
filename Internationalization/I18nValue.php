<?php


namespace Core\Internationalization;


abstract class I18nValue
{
    abstract function __toString(): string;
}