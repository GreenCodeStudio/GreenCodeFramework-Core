<?php

namespace Core\Console;

class MigrationConsole extends \Core\AbstractController
{
    function Upgrade()
    {
        $migr = \Core\Database\Migration::factory();
        $migr->upgrade();
        $migr->execute();
    }

    function UpgradeByFile(string $filename = null)
    {
        $migr = \Core\Database\Migration::factory();
        $migr->upgradeByFile($filename);
        $migr->execute();
    }

    function Preview()
    {
        $migr = \Core\Database\Migration::factory();
        $migr->upgrade();
        return $migr->queries;
    }

    function PreviewByFile(string $filename = null)
    {
        $migr = \Core\Database\Migration::factory();
        $migr->upgradeByFile($filename);
        return $migr->queries;
    }

    function Read()
    {
        $migr = \Core\Database\Migration::factory();
        return $migr->oldStructureToXml();
    }
}
