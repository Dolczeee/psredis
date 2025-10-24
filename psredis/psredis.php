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

        parent::__construct();

        $this->displayName = $this->l('PS Redis');
        $this->description = $this->l('Integracja Redis dla PrestaShop — cache i narzędzia administracyjne.');
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => '9.99.99'];
    }

    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('actionDispatcher')
            && $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall(): bool
    {
        return parent::uninstall();
    }

    public function getContent(): string
    {
        return '<div class="module-config"><h3>' . $this->displayName . '</h3></div>';
    }
}