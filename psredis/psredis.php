<?php
/**
 * Moduł Redis Cache & Performance Optimizer
 *
 * Zaawansowana integracja Redis dla PrestaShop 9.0 (kompatybilność od 8.1.0)
 * Autor: DolceCX
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Psredis extends Module
{
    public function __construct()
    {
        $this->name = 'psredis';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'DolceCX';
        $this->need_instance = 0;
        $this->bootstrap = true;

        // KROK 1: Rejestracja autoloadera musi nastąpić wcześnie
        $this->registerPsr4Autoloader(); // UWAGA: Teraz ma prostszą logikę

        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $this->_errors[] = $this->l('Ten moduł wymaga PHP 7.4 lub nowszego');
            return;
        }

        parent::__construct();

        $this->displayName = $this->l('PS Redis');
        $this->description = $this->l('Integracja Redis dla PrestaShop — cache i narzędzia administracyjne.');
        $this->ps_versions_compliancy = ['min' => '8.1.0', 'max' => '9.99.99'];
    }

    // --------------------------------------------------------------------------
    // ZARZĄDZANIE AUTOLOADEREM (Naprawia błąd "Class not found")
    // --------------------------------------------------------------------------

    /**
     * Rejestruje autoloader PSR-4 za pomocą mechanizmu PrestaShop/Symfony LUB
     * używa globalnego spl_autoload_register jako fallback.
     */
    private function registerPsr4Autoloader(): void
    {
        $autoloaderRegistered = false;

        // 1. Próba użycia mechanizmu PS/Symfony (preferowane)
        if (class_exists(\PrestaShop\PrestaShop\Adapter\Container\ContainerFinder::class)) {
            try {
                $containerFinder = \PrestaShop\PrestaShop\Adapter\Container\ContainerFinder::getInstance();
                if (method_exists($containerFinder, 'getAutoloader')) {
                    $autoloader = $containerFinder->getAutoloader();
                    $autoloader->addPsr4('Psredis\\', __DIR__ . '/src/');
                    $autoloaderRegistered = true;
                }
            } catch (\Throwable $e) {
                // Kontynuuj do fallbacku
            }
        }

        // 2. Fallback: Ręczna rejestracja (jeśli główny mechanizm zawiódł)
        if (!$autoloaderRegistered) {
             spl_autoload_register(function ($class) {
                $prefix = 'Psredis\\';
                $base_dir = __DIR__ . '/src/';

                if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
                    return;
                }

                $relative_class = substr($class, strlen($prefix));
                $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

                if (file_exists($file)) {
                    require_once $file;
                }
             }, true, true); // PREPEND: true - jest kluczowy dla wczesnego ładowania.
        }
    }
    
    // USUNIĘTO: private function registerManualAutoloader(): void
    // --------------------------------------------------------------------------
    // ŁADOWANIE PLIKU SERVICES.YML (Naprawia błędy z serwisami w kontenerze)
    // --------------------------------------------------------------------------

    /**
     * Wymusza załadowanie services.yml do kontenera DI, jeśli API jest dostępne.
     */
    public function boot()
    {
        // Wywołaj parent::boot() na początku, aby upewnić się, że kontekst jest gotowy.
        if (is_callable('parent::boot')) {
             try {
                 parent::boot();
             } catch (\Throwable $e) { /* ignoruj błędy */ }
        }

        // KROK KLUCZOWY: Wymuszamy załadowanie pliku konfiguracyjnego.
        try {
            if (method_exists($this, 'get') && class_exists(\PrestaShop\PrestaShop\Core\Addon\Module\ModuleServiceProvider::class)) {
                
                $servicesPath = method_exists($this, 'getLocalPath') ? $this->getLocalPath() . 'config/services.yml' : __DIR__ . '/config/services.yml';

                /** @var \PrestaShop\PrestaShop\Core\Addon\Module\ModuleServiceProvider $moduleServiceProvider */
                $moduleServiceProvider = $this->get('prestashop.core.addon.module.service_provider');
                
                if ($moduleServiceProvider && method_exists($moduleServiceProvider, 'loadServices') && file_exists($servicesPath)) {
                    $moduleServiceProvider->loadServices($servicesPath);
                }
            }
        } catch (\Throwable $e) {
            // Kontener może być w dziwnym stanie podczas kompilacji.
            // Ignorujemy błędy, jeśli mechanizm jest niedostępny.
        }
    }
    
    // --------------------------------------------------------------------------
    // STANDARDOWE METODY MODUŁU
    // --------------------------------------------------------------------------

    public function install(): bool
    {
        if (!extension_loaded('redis')) {
            $this->_errors[] = $this->l('Rozszerzenie Redis PHP nie jest zainstalowane');
            return false;
        }

        return parent::install() 
            && Configuration::updateValue('PSREDIS_ENABLED', false);
    }

    public function uninstall(): bool
    {
        return parent::uninstall()
            && Configuration::deleteByName('PSREDIS_ENABLED');
    }

    public function getContent(): string
    {
        return '<div class="module-config"><h3>' . $this->displayName . '</h3></div>';
    }
}