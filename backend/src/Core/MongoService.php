<?php

namespace App\Core;

use MongoDB\Client;

class MongoService
{
    private $client;
    private $db;

    public function __construct()
    {
        $uri = $_ENV['MONGO_URI'] ?? getenv('MONGO_URI');
        $this->client = new Client($uri);
        $this->db = $this->client->selectDatabase('viteEtGourmand');
    }

    public function getCollection($name)
    {
        return $this->db->$name;
    }
}