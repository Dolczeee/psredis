<?php

class PsredisRedisStatusControllerModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        // Ustawienie nagłówka JSON
        header('Content-Type: application/json');

        try {
            // Wstrzyknięcie usługi RedisConnectionService
            $connectionService = $this->module->get('psredis.service.connection');
            $client = $connectionService->getClient();

            if (!$client) {
                 throw new Exception("Cache Redis jest wyłączony lub niepoprawnie skonfigurowany.");
            }

            // Benchmark Redis - prosta operacja PING i mierzenie czasu
            $start = microtime(true);
            $ping_response = $client->ping(); // Zwróci PONG lub 1 w zależności od klienta/sterownika
            $duration = round((microtime(true) - $start) * 1000, 2); // w milisekundach

            $status = [
                'status' => 'OK',
                'connection_test' => 'Sukces',
                'ping_response' => is_array($ping_response) ? implode(', ', $ping_response) : (string) $ping_response,
                'latency_ms' => $duration,
                'server_info' => $client->info('server')['server'] ?? 'Niedostępne',
            ];

        } catch (Exception $e) {
            $status = [
                'status' => 'BŁĄD',
                'connection_test' => 'Niepowodzenie',
                'error_message' => $e->getMessage(),
            ];
        }

        die(json_encode($status, JSON_PRETTY_PRINT));
    }
}