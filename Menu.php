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
                    $root[] = $this->getAsStdClass($element);
                }
            }
        }
        return $root;
    }

    private function getAsStdClass(\SimpleXMLElement $element)
    {
        $ret = new \StdClass();
        foreach ($element->children() as $name => $value) {
            if ($name == 'menu') {
                $ret->menu = [];
                foreach ($value->children() as $childElement) {
                    $ret->menu[] = $this->getAsStdclass($childElement);
                }
            }
            else if ($name == 'permission') {
                $ret->permission=new \stdClass();
                foreach ($value as $childName=>$childElement) {
                    $ret->permission->$childName = $childElement->__toString();
                }
            } else if ($name=='title'){
                if($value->attributes()->key)
                    $ret->title=t($value->attributes()->key->__toString());
                else
                    $ret->title=$value->__toString();
            }else
                $ret->$name = $value->__toString();
        }
        return $ret;
    }
}
