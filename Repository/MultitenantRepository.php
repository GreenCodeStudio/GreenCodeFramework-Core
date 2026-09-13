<?php

namespace Core\Repository;

use Core\Database\DB;
use MKrawczyk\FunQuery\FunQuery;

class MultitenantRepository
{
    public function getAll()
    {
        if(empty($_ENV['dbMultitenantPrefix'])){
            throw new \Exception("dbMultitenantPrefix is not set in .env file");
        }
        $all = DB::get("SHOW DATABASES");
        dump($all);
        return FunQuery::from($all)->filter(fn($x) => str_starts_with($x->Database, $_ENV['dbMultitenantPrefix']))->map(fn($x) => $x->Database);
    }
}
