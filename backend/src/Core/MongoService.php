<?php

namespace App\Core;

use MongoDB\Client;

class MongoService
{
    private $client;
    private $db;

    public function __construct()
    {
        $this->client = new Client($_ENV['MONGO_URI']);
        $this->db = $this->client->viteetgourmand;
    }

    public function getCollection($name)
    {
        return $this->db->$name;
    }
}