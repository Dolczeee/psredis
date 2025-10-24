<?php

namespace Psredis\Hook;

use Psredis\Cache\RedisCacheAdapter;
use PrestaShopBundle\Service\Hook\HookSubscriberInterface;
use ObjectModel;

class CacheHookSubscriber implements HookSubscriberInterface
{
    private $cacheAdapter;

    public function __construct(RedisCacheAdapter $cacheAdapter)
    {
        $this->cacheAdapter = $cacheAdapter;
    }

    /**
     * Rejestruje hooki, na które subskrybent ma reagować.
     */
    public static function get==
Hooks(): array
    {
        // Reaguje na zapis, dodanie i usunięcie dowolnego obiektu ObjectModel
        return [
            'actionObjectUpdateAfter',
            'actionObjectAddAfter',
            'actionObjectDeleteAfter',
        ];
    }

    /**
     * Metoda wywoływana po zmianie obiektu (np. Produkt, Kategoria, Ustawienia)
     */
    public function handleActionObjectUpdateAfter(array $params)
    {
        $this->handleCacheClear($params);
    }

    public function handleActionObjectAddAfter(array $params)
    {
        $this->handleCacheClear($params);
    }

    public function handleActionObjectDeleteAfter(array $params)
    {
        $this->handleCacheClear($params);
    }

    /**
     * Logika czyszczenia pamięci podręcznej.
     * Automatyczne resetowanie pamięci podręcznej dla modelu obiektu.
     */
    private function handleCacheClear(array $params)
    {
        /** @var ObjectModel $object */
        $object = $params['object'] ?? null;

        if (!$object) {
            return;
        }

        $object_name = get_class($object);

        // Używamy nazwy klasy do identyfikacji kluczy w Redis (np. Product, Configuration)
        // Adapter Cache zajmie się faktycznym usunięciem kluczy
        $this->cacheAdapter->clearObjectCache($object_name, $object->id);
    }
}