<?php
/**
 * Created by PhpStorm.
 * User: matri
 * Date: 04.07.2018
 * Time: 17:55
 */

namespace Core;


class Menu
{
    function readMenu()
    {
        $root = [];
        $modules = scandir(__DIR__.'/../');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            $filename = __DIR__.'/../'.$module.'/menu.xml';
            if (is_file($filename)) {
                $xml = simplexml_load_string(file_get_contents($filename));
                foreach ($xml->children() as $element) {
                    $root[] = $this->getAsStdclass($element);
                }
            }
        }
        return $root;
    }

    private function getAsStdclass(\SimpleXMLElement $element)
    {
        $ret = new \StdClass();
        foreach ($element->children() as $name => $value) {
            if ($name == 'menu') {
                $ret->menu = [];
                foreach ($value->children() as $childElement) {
                    $ret->menu[] = $this->getAsStdclass($childElement);
                }
            } else
                $ret->$name = $value->__toString();
        }
        return $ret;
    }
}