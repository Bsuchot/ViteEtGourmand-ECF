<?php

namespace App\Core;
use PDO;

class DbConnection {

    private static $instance = null;

    private PDO $pdo;


    private function __construct()
    {
        $dbhost = $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_NAME'];
        $dbuser = $_ENV['DB_USER'];
        $dbpass = $_ENV['DB_PASS'];
        $this->pdo = new \PDO("mysql:host=$dbhost;dbname=$dbname;port=3306;charset=utf8", $dbuser, $dbpass);
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