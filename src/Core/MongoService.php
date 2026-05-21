<?php

namespace App\Core;

use MongoDB\Client;

class MongoService
{
    private $client;
    private $db;

    public function __construct()
    {
        $this->client = new Client(getenv('MONGO_URI'));
        $this->db     = $this->client->vite_et_gourmand;
    }

    public function getCollection($name)
    {
        return $this->db->$name;
    }
}