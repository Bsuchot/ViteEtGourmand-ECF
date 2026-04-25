<?php

namespace App\Core;
use App\Core\DbConnection;
use PDO;

class Repository
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = DbConnection::getPDO();
    }
}

