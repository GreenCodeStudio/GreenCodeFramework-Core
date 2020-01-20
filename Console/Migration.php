<?php

namespace Core\Console;

class Migration extends \Core\AbstractController
{
    function Upgrade(?string $filename = null)
    {
        $migr = \Core\Migration::factory();
        if ($filename === null)
            $migr->upgrade();
        else
            $migr->upgradeByFile($filename);

        $migr->execute();
    }

    function Preview(?string $filename = null)
    {
        $migr = \Core\Migration::factory();
        if ($filename === null)
            $migr->upgrade();
        else
            $migr->upgradeByFile($filename);

        return $migr->queries;
    }

    function Read()
    {
        $migr = \Core\Migration::factory();
        return $migr->oldStructureToXml();
    }
}
