<?php

namespace App\Core;
use PDO;

class DbConnection {

    private static $instance = null;

    private PDO $pdo;


    private function __construct()
    {
        $this->pdo = new \PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
    }

    public static function getInstance()
    {
        return self::createOrReturnInstance();
    }

    public static function getPDO()
    {
       return self::createOrReturnInstance()->pdo;
    }

    private static function createOrReturnInstance()
    {
        if(!self::$instance)
        {
            return self::$instance = new DbConnection();
        }
        return self::$instance;
    }
}