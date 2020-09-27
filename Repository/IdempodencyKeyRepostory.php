<?php


namespace Core\Repository;


class IdempodencyKeyRepostory
{
    public function Test(string $key)
    {
        return \Core\Database\MiniDB::GetConnection()->eval(/** @lang LUA */ "
        if redis.call(\"GET\", ARGV[1]) then
            return false
        else
            redis.call(\"SETEX\", ARGV[1], 86400, 1)
            return true
        end
", ["idempotency_key_".$key]);
    }
}