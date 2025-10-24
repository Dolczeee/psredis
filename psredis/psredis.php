<?php
/**
 * Moduł Redis Cache & Performance Optimizer
 *
 * Zaawansowana integracja Redis dla PrestaShop 9.0 (kompatybilność od 8.1.0)
 * Autor: Generowany przez AI
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleInterface;

class Psredis extends Module implements ModuleInterface
{
    public function __construct()
    {
        $this->name = 'psredis';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Twoja Nazwa';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.1.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Redis Cache & Performance Optimizer');
        $this->description = $this->l('Zaawansowany moduł Redis dla PrestaShop 9.0: redukcja zapytań, obsługa wielu serwerów i optymalizacja TTFB.');

        // Sprawdzenie, czy konfiguracja Redis istnieje
        if (!file_exists($this->getConnectionFilePath())) {
            $this->warning = $this->l('Wymagana konfiguracja połączenia Redis. Przejdź do konfiguracji modułu.');
        }
        
        // Definicja tab (dla kontrolera administracyjnego)
        $this->tabs = [
            [
                'name' => 'Redis Management',
                'class_name' => 'RedisAdmin',
                'parent_class_name' => 'CONFIGURE',
                'visible' => true,
                'position' => 99, // Umieszczenie na dole listy
            ],
        ];
    }

    /**
     * Główna funkcja instalacji modułu
     */
    public function install()
    {
        // Instalacja Tab w panelu administracyjnym
        if (!parent::install() || !$this->installTabs()) {
            return false;
        }

        return $this->registerHooks()
            && $this->registerConfiguration();
    }

    /**
     * Główna funkcja deinstalacji modułu
     */
    public function uninstall()
    {
        // Deinstalacja Tab
        if (!$this->uninstallTabs() || !parent::uninstall()) {
            return false;
        }

        // Usuwanie konfiguracji i bezpiecznego pliku połączenia
        Configuration::deleteByName('PSREDIS_ENABLED');
        Configuration::deleteByName('PSREDIS_EXCLUDED_CONTROLLERS');
        Configuration::deleteByName('PSREDIS_WEBSERVICE_DISABLED');
        
        if (file_exists($this->getConnectionFilePath())) {
            unlink($this->getConnectionFilePath());
        }

        return true;
    }

    /**
     * Rejestracja kluczowych hooków PrestaShop
     */
    protected function registerHooks()
    {
        return $this->registerHook('actionDispatcherBefore') // Wykluczanie kontrolerów i web services
            // Hooki dla subskrybenta (actionObject*After) są rejestrowane przez services.yml -> hook.subscriber
            && $this->registerHook('moduleRoutes'); // Dodanie trasy dla statusu JSON
    }

    /**
     * Wstępna konfiguracja
     */
    protected function registerConfiguration()
    {
        // Domyślne włączenie modułu
        Configuration::updateValue('PSREDIS_ENABLED', 1);
        Configuration::updateValue('PSREDIS_EXCLUDED_CONTROLLERS', 'order,checkout,cart');
        Configuration::updateValue('PSREDIS_WEBSERVICE_DISABLED', 1);
        return true;
    }

    /**
     * Generuje ścieżkę do bezpiecznego pliku konfiguracyjnego Redis.
     */
    protected function getConnectionFilePath()
    {
        return _PS_ROOT_DIR_ . '/app/config/redis_connection.json';
    }

    /**
     * Włączenie integracji z kontenerem DI, gdy moduł jest aktywny.
     */
    public function boot()
    {
        // Uruchamiamy tylko, jeśli jest włączony w konfiguracji i nie jest globalnie wyłączony
        if (Configuration::get('PSREDIS_ENABLED') && !defined('PSREDIS_DISABLE_CACHE')) {
            $container = $this->getContainer();
            
            if ($container instanceof \Symfony\Component\DependencyInjection\ContainerBuilder) {
                try {
                    // 1. Ładowanie definicji serwisów z config/services.yml
                    $loader = new YamlFileLoader(
                        $container,
                        new FileLocator($this->getLocalPath() . 'config')
                    );
                    $loader->load('services.yml');
                    
                    // 2. Przesłonięcie standardowego adaptera cache naszym RedisAdapter
                    if ($container->has('prestashop.cache.adapter')) {
                        $container->setAlias(
                            'prestashop.cache.adapter', 
                            'psredis.cache.adapter'
                        )->setPublic(true);
                        
                        // Przesłonięcie adaptera cache Smarty
                        if ($container->has('smarty.cache_adapter')) {
                            $container->setAlias(
                                'smarty.cache_adapter', 
                                'psredis.cache.adapter'
                            )->setPublic(true);
                        }

                        // Osiągnięto redukcję zapytań!
                    }
                    
                } catch (\Exception $e) {
                    // Logowanie błędu ładowania konfiguracji DI
                    PrestaShopLogger::addLog('Psredis: Błąd ładowania konfiguracji DI: ' . $e->getMessage(), 3, null, 'Module', $this->id, true);
                    // Opcjonalnie: Wyłączenie modułu w przypadku krytycznego błędu DI.
                }
            }
        }
    }
    
    /**
     * Wyświetlanie konfiguracji w panelu admina
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('RedisAdmin', true));
    }

    /**
     * Hook: actionDispatcherBefore (do wykluczania kontrolerów i web services)
     */
    public function hookActionDispatcherBefore($params)
    {
        if (!Configuration::get('PSREDIS_ENABLED')) {
            return;
        }
        
        // Zmienna globalna do ręcznego wyłączania w kodzie (jeśli jest ustawiona, pomijamy dalsze kroki)
        if (defined('PSREDIS_DISABLE_CACHE')) {
            return;
        }

        $controller = Tools::getValue('controller');

        // 1. Wykluczenie kontrolerów
        $excluded_controllers = array_map('trim', explode(',', Configuration::get('PSREDIS_EXCLUDED_CONTROLLERS')));
        if (in_array($controller, $excluded_controllers)) {
            define('PSREDIS_DISABLE_CACHE', true);
            return;
        }

        // 2. Wyłączenie dla Web Services
        // Sprawdzenie czy żądanie jest żądaniem API
        $isWebService = (bool) Tools::getValue('ws_key'); 

        if ($isWebService && (int) Configuration::get('PSREDIS_WEBSERVICE_DISABLED') === 1) {
            define('PSREDIS_DISABLE_CACHE', true);
            return;
        }
    }
    
    /**
     * Hook: Dodanie trasy dla kontrolera statusu JSON
     */
    public function hookModuleRoutes($params)
    {
        return [
            'module-psredis-redisstatus' => [
                'controller' => 'redisstatus',
                'rule' => 'redis-status',
                'keywords' => [],
                'params' => [
                    'module' => 'psredis',
                    'fc' => 'module',
                ],
            ],
        ];
    }
    
    // Metody pomocnicze dla instalacji/deinstalacji Tab (można przenieść do Traits)
    protected function installTabs()
    {
        $result = true;
        foreach ($this->tabs as $tab) {
            $newTab = new Tab();
            $newTab->class_name = $tab['class_name'];
            $newTab->module = $this->name;
            $newTab->id_parent = (int)Tab::getIdFromClassName($tab['parent_class_name'] ?: 'AdminAdvancedParameters');
            $newTab->active = 1;
            
            foreach (Language::getLanguages() as $lang) {
                $newTab->name[$lang['id_lang']] = $tab['name'];
            }
            
            $result &= $newTab->add();
        }
        return $result;
    }
    
    protected function uninstallTabs()
    {
        $result = true;
        foreach ($this->tabs as $tab) {
            $id_tab = (int)Tab::getIdFromClassName($tab['class_name']);
            if ($id_tab) {
                $myTab = new Tab($id_tab);
                $result &= $myTab->delete();
            }
        }
        return $result;
    }
}