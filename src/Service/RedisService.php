<?php

namespace App\Service;

use App\Entity\User;
use Redis;

class RedisService
{
    const HOST = 'redis';
    const PORT = 6379;
    const API_KEY_DB = 1; // api_key => user_id

    protected $client;

    public function __construct()
    {
        $redis = new Redis();
        $redis->connect(self::HOST, self::PORT);
        $this->client = $redis;
    }

    public function setApiKey(User $user, string $apiKey)
    {
        $this->client->select(self::API_KEY_DB);
        $this->client->append($apiKey, $user->getId());
    }

    public function getUserIdByApiKey(string $apiKey)
    {
        $this->client->select(self::API_KEY_DB);
        $result = $this->client->get($apiKey);

        return $result;
    }
}
