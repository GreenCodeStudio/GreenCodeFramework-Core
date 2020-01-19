<?php

include_once __DIR__.'/../../vendor/autoload.php';

use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Dotenv;

$repository = RepositoryBuilder::create()
    ->withReaders([
        new EnvConstAdapter(),
    ])
    ->withWriters([
        new EnvConstAdapter(),
        new ServerConstAdapter(),
    ])
    ->immutable()
    ->make();
$dotenv = Dotenv::create($repository, __DIR__.'/../../');
$dotenv->load();