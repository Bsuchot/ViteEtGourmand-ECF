<?php

namespace App\Core;

use MongoDB\Client;

class MongoService
{
    private $client;
    private $db;

    public function __construct()
    {
        $this->client = new Client("mongodb://localhost:27017");
        $this->db = $this->client->viteEtGourmand;
    }

    public function getCollection($name)
    {
        return $this->db->$name;
    }
}