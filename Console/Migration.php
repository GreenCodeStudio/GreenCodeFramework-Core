<?php

namespace Core\Console;

class Migration extends \Core\AbstractController
{
    function Upgrade()
    {
        $migr = \Core\Migration::factory();
        $migr->upgrade();
        $migr->execute();
    }

    function UpgradeByFile(string $filename = null)
    {
        $migr = \Core\Migration::factory();
        $migr->upgradeByFile($filename);
        $migr->execute();
    }

    function Preview()
    {
        $migr = \Core\Migration::factory();
        $migr->upgrade();
        return $migr->queries;
    }

    function PreviewByFile(string $filename = null)
    {
        $migr = \Core\Migration::factory();
        $migr->upgradeByFile($filename);
        return $migr->queries;
    }

    function Read()
    {
        $migr = \Core\Migration::factory();
        return $migr->oldStructureToXml();
    }
}
