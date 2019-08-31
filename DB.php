<?php

namespace Core;

class DB
{
    /**
     * @var \PDO
     */
    private static $pdo = null;
    private static $dialect = 'mysql';
    private static $dsn;
    private static $user;
    private static $password;

    static function get(string $sql, $params = [])
    {
        static::connect();
        $sth = static::$pdo->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $params2 = [];
        foreach ($params as $name => $value) {
            $params2[':'.$name] = $value;
        }
        $sth->execute(array_map('self::toSqlValue', $params));
        $ret = $sth->fetchAll(\PDO::FETCH_CLASS, 'stdClass');
        return $ret;
    }

    static function connect()
    {
        if (static::$pdo === null) {
            static::$pdo = new \PDO(static::$dsn, static::$user, static::$password);
            static::$password = null;
            static::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
    }

    /**
     * @param string $sql
     * @param array $params
     * @return array
     */
    static function getArray(string $sql, $params = [])
    {
        static::connect();
        $sth = static::$pdo->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $params2 = [];
        foreach ($params as $name => $value) {
            $params2[':'.$name] = $value;
        }
        $sth->execute(array_map('self::toSqlValue', $params));
        $ret = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $ret;
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
            $nameSafe = static::safeKey($name);
            $update[] = " $nameSafe = :$name";
            $dataSql[$name] = $value;
        }
        $updateJoined = implode(',', $update);
        $tableSafe = static::safeKey($table);
        static::query("UPDATE $tableSafe SET $updateJoined WHERE id = :id", $dataSql);
    }

    static function clearName(string $name)
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }

    static function safeKey($val)
    {
        static::connect();
        $clean = preg_replace('/[^A-Za-z0-9_]+/', '', $val);
        if (static::$dialect == 'mysql')
            return '`'.$clean.'`'; else
            return '"'.$clean.'"';
    }

    static function query(string $sql, $params = [])
    {
        static::connect();
        dump($sql);
        $sth = static::$pdo->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $params2 = [];
        foreach ($params as $name => $value) {
            $params2[':'.$name] = $value;
        }
        $sth->execute(array_map('self::toSqlValue', $params));
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
            $nameSafe = static::safeKey($name);
            $cols[] = " $nameSafe ";
            $values[] = " :$name ";
            $dataSql[$name] = $value;
        }
        $colsJoined = implode(',', $cols);
        $valuesJoined = implode(',', $values);
        $tableSafe = static::safeKey($table);
        static::query("INSERT INTO $tableSafe ($colsJoined) VALUES ($valuesJoined)", $dataSql);
        return static::$pdo->lastInsertId();
    }

    static private function toSqlValue($input)
    {
        if ($input instanceof \DateTime)
            return $input->format('Y-m-d H:i:s.v');
        else return $input;
    }

    static function insertMultiple(string $table, array $data)
    {
        if (empty($data))
            return null;
        static::connect();
        $table = static::clearName($table);
        $cols = [];
        $dataSql = [];
        $example = [];
        foreach ($data as $row) {
            $example += $row;
        }

        foreach ($example as $name => $value) {
            $name = static::clearName($name);
            $cols[] = " `$name` ";
        }
        $valuesJoinedArray = [];
        foreach ($data as $i => $row) {
            $values = [];
            foreach ($example as $name => $value) {
                $nameCleared = static::clearName($name).'_'.$i;
                $values[] = " :$nameCleared ";
                $dataSql[$nameCleared] = $row[$name] ?? NULL;
            }
            $valuesJoinedArray[] = '('.implode(',', $values).')';
        }
        $colsJoined = implode(',', $cols);
        $valuesJoinedJoined = implode(',', $valuesJoinedArray);
        static::query("INSERT INTO `$table` ($colsJoined) VALUES $valuesJoinedJoined", $dataSql);
        return static::$pdo->lastInsertId();
    }

    public static function init()
    {

        DB::$dialect = getenv('dbDialect');
        DB::$dsn = getenv('db');
        DB::$user = getenv('dbUser');
        DB::$password = getenv('dbPass');
    }
}