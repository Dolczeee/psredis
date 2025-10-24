<?php

namespace Psredis\Service;

use Exception;
use Configuration;

class RedisConnectionService
{
    private $connectionFilePath;
    private $connections = [];
    private $defaultClient;

    public function __construct(string $modulePath)
    {
        // Ścieżka do pliku konfiguracyjnego (np. /app/config/redis_connection.json)
        $this->connectionFilePath = $modulePath . '/../app/config/redis_connection.json';
        $this->loadConnections();
    }

    /**
     * Wczytuje konfigurację połączeń z bezpiecznego pliku.
     */
    private function loadConnections()
    {
        if (file_exists($this->connectionFilePath)) {
            $json_data = file_get_contents($this->connectionFilePath);
            $this->connections = json_decode($json_data, true);
        } else {
            $this->connections = [];
        }
    }

    /**
     * Zapisuje konfigurację połączeń do bezpiecznego pliku.
     */
    public function saveConnections(array $data)
    {
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        if (file_put_contents($this->connectionFilePath, $json_data)) {
            $this->connections = $data;
            return true;
        }
        return false;
    }

    /**
     * Tworzy i zwraca klienta Redis (domyślnie pierwszy serwer z listy).
     * Obsługa Predis, PHPRedis (i wsparcie dla KeyDB/DragonflyDB).
     */
    public function getClient(string $serverId = null)
    {
        if (defined('PSREDIS_DISABLE_CACHE') || !Configuration::get('PSREDIS_ENABLED')) {
            return null; // Cache wyłączony
        }

        if ($this->defaultClient) {
            return $this->defaultClient;
        }

        if (empty($this->connections)) {
            throw new Exception('Brak skonfigurowanych serwerów Redis.');
        }

        $connection = $serverId ? ($this->connections[$serverId] ?? null) : reset($this->connections);

        if (!$connection) {
            throw new Exception('Nie znaleziono żądanego połączenia Redis.');
        }

        $host = $connection['host'];
        $port = $connection['port'];
        $driver = $connection['driver']; // np. 'predis', 'phpredis'

        try {
            if ($driver === 'phpredis' && extension_loaded('redis')) {
                $redis = new \Redis();
                // Obsługa gniazda Unix
                if (isset($connection['socket']) && $connection['socket']) {
                    $redis->connect($connection['socket']);
                } else {
                    $redis->connect($host, $port);
                }
                
                // Autoryzacja i wybór bazy danych
                if (isset($connection['password']) && $connection['password']) {
                    $redis->auth($connection['password']);
                }
                if (isset($connection['database']) && $connection['database'] !== '') {
                    $redis->select((int)$connection['database']);
                }
                $this->defaultClient = $redis;
            } elseif ($driver === 'predis') {
                // Implementacja Predis
                $parameters = [
                    'scheme' => 'tcp',
                    'host'   => $host,
                    'port'   => $port,
                ];
                if (isset($connection['password']) && $connection['password']) {
                    $parameters['password'] = $connection['password'];
                }
                if (isset($connection['database']) && $connection['database'] !== '') {
                    $parameters['database'] = $connection['database'];
                }

                $this->defaultClient = new \Predis\Client($parameters);
                $this->defaultClient->connect();
            } else {
                throw new Exception('Nieobsługiwany sterownik Redis: ' . $driver);
            }

            return $this->defaultClient;

        } catch (Exception $e) {
            // Wyrzuć błąd połączenia
            throw new Exception("Błąd połączenia z Redis ({$host}:{$port}): " . $e->getMessage());
        }
    }

    /**
     * Zwraca listę skonfigurowanych połączeń (dla interfejsu administracyjnego)
     */
    public function getConnections()
    {
        return $this->connections;
    }
}