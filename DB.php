<?php

namespace Core;

class DB
{
    /**
     * @var \PDO
     */
    private static $pdo = null;

    static function get(string $sql, $params = [])
    {
        static::connect();
        $sth = static::$pdo->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $params2 = [];
        foreach ($params as $name => $value) {
            $params2[':'.$name] = $value;
        }
        $sth->execute($params);
        $ret = $sth->fetchAll();
        return $ret;
    }

    static function connect()
    {
        if (static::$pdo === null) {
            static::$pdo = new \PDO(getenv('db'), getenv('dbUser'), getenv('dbPass'));
            static::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
    }

    static function rollBack()
    {
        static::connect();
        static::$pdo->rollback();
    }

    static function commit()
    {
        static::connect();
        static::$pdo->commit();
    }

    static function beginTransaction()
    {
        static::connect();
        static::$pdo->beginTransaction();
    }

    static function lastInsertId()
    {
        static::connect();
        return static::$pdo->lastInsertId();
    }

    static function safe($val)
    {
        static::connect();
        if ($val === NULL)
            return null;
        if (is_int($val))
            return (int)$val;
        return "'".static::$pdo->quote($val)."'";
    }

    static function update(string $table, $data, $id)
    {
        static::connect();
        $table = static::clearName($table);
        $update = [];
        $dataSql = ['id' => $id];
        foreach ($data as $name => $value) {
            $name = static::clearName($name);
            $update[] = " `$name` = :$name";
            $dataSql[$name] = $value;
        }
        $updateJoined = implode(',', $update);
        static::query("UPDATE `$table` SET $updateJoined WHERE id = :id", $dataSql);
    }

    static function clearName(string $name)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $name);
    }

    static function query(string $sql, $params = [])
    {
        static::connect();
        $sth = static::$pdo->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $params2 = [];
        foreach ($params as $name => $value) {
            $params2[':'.$name] = $value;
        }
        $sth->execute($params);
    }

    static function insert(string $table, $data)
    {
        static::connect();
        $table = static::clearName($table);
        $cols = [];
        $values = [];
        $dataSql = [];
        foreach ($data as $name => $value) {
            $name = static::clearName($name);
            $cols[] = " `$name` ";
            $values[] = " :$name ";
            $dataSql[$name] = $value;
        }
        $colsJoined = implode(',', $cols);
        $valuesJoined = implode(',', $values);
        static::query("INSERT INTO `$table` ($colsJoined) VALUES ($valuesJoined)", $dataSql);
        return static::$pdo->lastInsertId();
    }
}
