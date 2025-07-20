<?php

use Mkrawczyk\Internationalization\I18nNodeNotFoundException;
use Mkrawczyk\Internationalization\LanguagesHierarchy;
use Mkrawczyk\Internationalization\Translator;
use Mkrawczyk\Internationalization\TextsRepository;


$repository = new TextsRepository();
$modules = scandir(__DIR__.'/../');
foreach ($modules as $module) {
    if ($module == '.' || $module == '..') {
        continue;
    }
    $filename = __DIR__.'/../'.$module.'/i18n.xml';
    if (is_file($filename)) {
        $repository->loadModuleFile($module, $filename);
    }
}
Translator::$default = new Translator($repository, LanguagesHierarchy::ReadFromUser());


function t($q)
{
    if (getenv('debug') == 'true') {
        $value = Translator::$default->translate($q);
    } else {
        try {
            $value = Translator::$default->translate($q);
        } catch (I18nNodeNotFoundException $exception) {
            trigger_error("Key not found in translations: $q", E_USER_WARNING);
            return '';
        }
    }
    if ($value === null) {
        return '';
    } else {
        return $value."";
    }
}
