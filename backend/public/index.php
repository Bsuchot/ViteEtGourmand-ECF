<?php

use App\Core\DbConnection;
use Dotenv\Dotenv;


require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

DbConnection::getPDO();

