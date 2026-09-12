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
    function UpgradeMultitenant()
    {
        $tenants=\Core\Database\Migration::listTenants();
        foreach($tenants as $tenant) {
            try {
                dump("Upgrading tenant $tenant");
                $migr = \Core\Database\Migration::factory($tenant);
                $migr->upgrade();
                $migr->execute();
            }catch (\Throwable $exception){
                dump($exception);
            }
        }
    }

    function UpgradeByFile(?string $filename = null)
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

    function PreviewByFile(?string $filename = null)
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
