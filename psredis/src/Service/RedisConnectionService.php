<?php

namespace Psredis\Service;

class RedisConnectionService
{
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client ?? $this->createClient();
    }

    private function createClient()
    {
        if (class_exists(\Redis::class)) {
            $redis = new \Redis();
            @ $redis->connect('127.0.0.1', 6379);
            return $redis;
        }

        if (class_exists(\Predis\Client::class)) {
            return new \Predis\Client();
        }

        return null;
    }

    public function isAvailable(): bool
    {
        try {
            if ($this->client instanceof \Redis) {
                $pong = $this->client->ping();
                return $pong === '+PONG' || $pong === 'PONG' || $pong === true;
            }

            if ($this->client && method_exists($this->client, 'ping')) {
                $res = $this->client->ping();
                return (bool) $res;
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    public function warmup(): void
    {
        if (!$this->client) {
            throw new \RuntimeException('Redis client is not available');
        }

        $key = 'psredis:warmup:' . bin2hex(random_bytes(4));
        $value = '1';

        try {
            if ($this->client instanceof \Redis) {
                $this->client->set($key, $value, 5);
                $this->client->get($key);
                $this->client->del($key);
                return;
            }

            if (method_exists($this->client, 'set')) {
                $this->client->set($key, $value);
                if (method_exists($this->client, 'get')) {
                    $this->client->get($key);
                }
                if (method_exists($this->client, 'del')) {
                    $this->client->del([$key]);
                } elseif (method_exists($this->client, 'delete')) {
                    $this->client->delete($key);
                }
                return;
            }

            throw new \RuntimeException('Unsupported Redis client implementation');
        } catch (\Throwable $e) {
            throw new \RuntimeException('Redis warmup failed: ' . $e->getMessage(), 0, $e);
        }
    }
}