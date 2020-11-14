<?php


namespace Core\Internationalization;


class TextsRepository
{
    static private ?I18nNode $root = null;

    static public function getRootNode():I18nNode
    {
        self::loadIfNeeded();
        return self::$root;
    }

    static public function loadIfNeeded()
    {
        if (self::$root === null)
            self::load();
    }

    static private function load()
    {
        self::$root = new I18nNode();
        $modules = scandir(__DIR__.'/../../');
        foreach ($modules as $module) {
            if ($module == '.' || $module == '..') {
                continue;
            }
            $filename = __DIR__.'/../../'.$module.'/i18n.xml';
            if (is_file($filename)) {
                self::$root->addChild($module, self::parseFile($filename));
            }
        }
    }

    private static function parseFile(string $filename)
    {
        $xml = \simplexml_load_string(file_get_contents($filename));
        if($xml===false)throw new \Exception("Bad i18n.xml file");
        return new I18nNode($xml);
    }
}