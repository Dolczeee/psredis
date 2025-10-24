<?php

class RedisAdminController extends ModuleAdminController
{
    /** @var Psredis */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->table = 'configuration';
        $this->className = 'Configuration';
        $this->identifier = 'id_configuration';

        parent::__construct();

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
        
        $this->tpl_form_vars = [
            'psredis_connections' => $this->module->get('psredis.service.connection')->getConnections(),
            'redis_status_url' => $this->context->link->getModuleLink('psredis', 'redisstatus'),
        ];
    }

    public function postProcess()
    {
        // Obsługa formularza (zapisywanie ustawień ogólnych i reguł)
        if (Tools::isSubmit('submitPsredisConfig')) {
            $this->processGeneralSettings();
        } 
        
        // Obsługa dodawania/edycji serwerów Redis
        if (Tools::isSubmit('submitPsredisServer')) {
            $this->processServerConfiguration();
        }
        
        // Obsługa czyszczenia cache
        if (Tools::isSubmit('submitPsredisClearCache')) {
            $this->processClearCache();
        }
        
        parent::postProcess();
    }
    
    /**
     * Zapisuje ustawienia ogólne (włączenie/wyłączenie, wykluczone kontrolery).
     */
    protected function processGeneralSettings()
    {
        Configuration::updateValue('PSREDIS_ENABLED', (int)Tools::getValue('PSREDIS_ENABLED'));
        Configuration::updateValue('PSREDIS_EXCLUDED_CONTROLLERS', Tools::getValue('PSREDIS_EXCLUDED_CONTROLLERS'));
        Configuration::updateValue('PSREDIS_WEBSERVICE_DISABLED', (int)Tools::getValue('PSREDIS_WEBSERVICE_DISABLED'));
        
        $this->confirmations[] = $this->l('Ustawienia ogólne zostały zaktualizowane.');
    }

    /**
     * Zapisuje/aktualizuje konfigurację serwerów do bezpiecznego pliku JSON.
     */
    protected function processServerConfiguration()
    {
        $connectionService = $this->module->get('psredis.service.connection');
        $connections = $connectionService->getConnections();
        
        $newConnection = [
            'name' => Tools::getValue('redis_server_name'),
            'host' => Tools::getValue('redis_host'),
            'port' => (int)Tools::getValue('redis_port'),
            'driver' => Tools::getValue('redis_driver'),
            'socket' => Tools::getValue('redis_socket'),
            'password' => Tools::getValue('redis_password'),
            'database' => Tools::getValue('redis_database'),
        ];
        
        // Użyj unikalnego identyfikatora (np. nazwy)
        $connections[$newConnection['name']] = $newConnection;
        
        if ($connectionService->saveConnections($connections)) {
            $this->confirmations[] = $this->l('Konfiguracja serwera Redis została zapisana.');
        } else {
            $this->errors[] = $this->l('Błąd zapisu pliku konfiguracyjnego.');
        }
    }
    
    /**
     * Wywołuje czyszczenie całego cache Redis.
     */
    protected function processClearCache()
    {
        /** @var \Psredis\Cache\RedisCacheAdapter $adapter */
        $adapter = $this->module->get('psredis.cache.adapter');
        if ($adapter->clear()) {
             $this->confirmations[] = $this->l('Pamięć podręczna Redis została całkowicie wyczyszczona.');
        } else {
             $this->errors[] = $this->l('Błąd podczas czyszczenia pamięci podręcznej Redis.');
        }
    }

    /**
     * Renderuje główny formularz konfiguracyjny (ustawienia, serwery, CRON).
     */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Ustawienia Główne Modułu Redis'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Aktywuj Cache Redis'),
                        'name' => 'PSREDIS_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Tak')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Nie')],
                        ],
                        'hint' => $this->l('Główny przełącznik włączający system buforowania Redis.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Wykluczone Kontrolery (CSV)'),
                        'name' => 'PSREDIS_EXCLUDED_CONTROLLERS',
                        'hint' => $this->l('Wpisz nazwy kontrolerów (np. order, checkout), które mają być pominięte w cache. Oddzielaj przecinkami.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Wyłącz Cache dla Web Services'),
                        'name' => 'PSREDIS_WEBSERVICE_DISABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Tak')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Nie')],
                        ],
                        'hint' => $this->l('Wyłącz buforowanie dla żądań API/integracji zewnętrznych, aby uniknąć problemów ze stanami magazynowymi/zamówieniami.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz Ustawienia Główne'),
                    'name' => 'submitPsredisConfig',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPsredisConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('RedisAdmin', false);
        $helper->token = Tools::getAdminTokenLite('RedisAdmin');

        // Wczytanie bieżących wartości
        $helper->tpl_vars['fields_value']['PSREDIS_ENABLED'] = Configuration::get('PSREDIS_ENABLED');
        $helper->tpl_vars['fields_value']['PSREDIS_EXCLUDED_CONTROLLERS'] = Configuration::get('PSREDIS_EXCLUDED_CONTROLLERS');
        $helper->tpl_vars['fields_value']['PSREDIS_WEBSERVICE_DISABLED'] = Configuration::get('PSREDIS_WEBSERVICE_DISABLED');
        
        $form = $helper->generateForm([$fields_form]);

        // Dodanie niestandardowych sekcji (Serwery, CRON, Diagnostyka)
        return $form
            . $this->renderServerConfigurationForm()
            . $this->renderDiagnosticsAndTools();
    }

    /**
     * Formularz dodawania/edycji serwera Redis.
     */
    protected function renderServerConfigurationForm()
    {
        // To jest uproszczona wersja, która używa szablonu do wyświetlenia listy i formularza dodawania
        $this->context->smarty->assign($this->tpl_form_vars);
        
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/server_config.tpl');
    }
    
    /**
     * Sekcja diagnostyki, CRON i narzędzi.
     */
    protected function renderDiagnosticsAndTools()
    {
        $cron_warmup_command = "php " . _PS_ROOT_DIR_ . "/bin/console psredis:warmup-cache";
        $cron_reset_command = $this->context->link->getAdminLink('RedisAdmin', true) . '&submitPsredisClearCache=1';
        
        $output = '
            <div class="panel">
                <div class="panel-heading"><i class="icon-refresh"></i> ' . $this->l('Czyszczenie Pamięci Podręcznej') . '</div>
                <form method="post" action="' . Tools::htmlentitiesUTF8($this->context->link->getAdminLink('RedisAdmin')) . '">
                    <button type="submit" name="submitPsredisClearCache" class="btn btn-danger">
                        <i class="icon-trash"></i> ' . $this->l('Wyczyść Cały Cache Redis') . '
                    </button>
                </form>
            </div>
            <div class="panel">
                <div class="panel-heading"><i class="icon-calendar"></i> ' . $this->l('Konfiguracja CRON') . '</div>
                <p>' . $this->l('Aby włączyć Pre-caching, dodaj poniższą komendę do zadań CRON na serwerze:') . '</p>
                <div class="alert alert-info">
                    <code>' . $cron_warmup_command . '</code>
                </div>
                <p>' . $this->l('Aby zautomatyzować całkowite resetowanie (np. raz dziennie), użyj tego URL (lub komendy konsolowej):') . '</p>
                <div class="alert alert-info">
                    <code>' . $cron_reset_command . '</code>
                </div>
            </div>
            <div class="panel">
                <div class="panel-heading"><i class="icon-check"></i> ' . $this->l('Diagnostyka i Benchmark') . '</div>
                <p>' . $this->l('Użyj poniższego URL do sprawdzenia statusu i opóźnienia połączenia Redis (benchmark):') . '</p>
                <div class="alert alert-success">
                    <a href="' . $this->tpl_form_vars['redis_status_url'] . '" target="_blank">
                        ' . $this->tpl_form_vars['redis_status_url'] . ' <i class="icon-external-link"></i>
                    </a>
                </div>
                <p><i>' . $this->l('Uwaga: Dostępne informacje o połączeniach Redis wyświetlają się w sekcji konfiguracji serwerów.') . '</i></p>
            </div>
        ';
        return $output;
    }
}