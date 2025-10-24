<?php

namespace Psredis\Cache;

use PrestaShop\PrestaShop\Adapter\Cache\CacheClearer;
use Psredis\Service\RedisConnectionService;
use Exception;

class RedisCacheAdapter implements CacheClearer
{
    private $connectionService;
    private $redisClient;
    
    // Lista tabel/modeli do pominięcia w automatycznym czyszczeniu
    private const EXCLUDED_CACHE_KEYS = [
        'Configuration', // Przykład, jeśli chcemy uniknąć konfliktu w trakcie zapisu
    ];

    public function __construct(RedisConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
        
        // Inicjalizacja klienta Redis (Predis/PHPRedis/KeyDB/DragonflyDB)
        try {
            // W zależności od konfiguracji, ten serwis zwróci poprawny obiekt klienta
            $this->redisClient = $this->connectionService->getClient();
        } catch (Exception $e) {
            // W przypadku błędu, wracamy do domyślnego cache (np. FileSystem)
            // lub logujemy błąd.
            $this->redisClient = null;
        }
    }

    public function get($key)
    {
        if (!$this->redisClient) {
            return false;
        }
        $data = $this->redisClient->get($key);
        return $data ? unserialize($data) : false;
    }

    public function set($key, $value, $ttl = 0)
    {
        if (!$this->redisClient) {
            return false;
        }
        $serialized_value = serialize($value);
        if ($ttl > 0) {
            return $this->redisClient->setex($key, $ttl, $serialized_value);
        } else {
            return $this->redisClient->set($key, $serialized_value);
        }
    }
    
    // ... Implementacja pozostałych metod cache (delete, exists, flush, etc.)

    public function clear()
    {
        if ($this->redisClient) {
            return $this->redisClient->flushdb(); // CRON do resetowania pamięci podręcznej
        }
        return false;
    }

    /**
     * Automatyczne czyszczenie pamięci podręcznej dla modelu obiektu
     * Wywoływane przez HookSubscriber
     */
    public function clearObjectCache($object_name, $id = null)
    {
        if (in_array($object_name, self::EXCLUDED_CACHE_KEYS)) {
            return;
        }
        
        // Logika czyszczenia dla konkretnego obiektu (np. Prefiks + nazwa obiektu)
        // W Redis często używa się 'DEL key' lub 'KEYS prefix:*' i potem DEL
        if ($this->redisClient) {
            $prefix = 'ps:object:' . strtolower($object_name);
            $keys = $this->redisClient->keys($prefix . '*');
            if ($keys) {
                $this->redisClient->del($keys);
            }
        }
    }
}