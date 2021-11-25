<?php

/**
 * mobbex_marketplace.php
 *
 * Main file of the module
 *
 * @author  Mobbex Co <admin@mobbex.com>
 * @version 2.6.0
 * @see     PaymentModuleCore
 */

if (!defined('_PS_VERSION_'))
    exit;

require dirname(__FILE__) . '/classes/MarketplaceHelper.php';

/**
 * Main class of the module
 */
class Mobbex_Marketplace extends Module
{
    /** @var Mobbex_Marketplace_Updater */
    public $updater;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->name            = 'mobbex_marketplace';
        $this->tab             = 'payments_gateways';
        $this->version         = MarketplaceHelper::MOBBEX_MARKETPLACE_VERSION;
        $this->author          = 'Mobbex Co';
        $this->currencies_mode = 'checkbox';
        $this->bootstrap       = true;

        parent::__construct();

        $this->displayName            = $this->l('Mobbex Marketplace');
        $this->description            = $this->l('Plugin de marketplace para Mobbex');
        $this->confirmUninstall       = $this->l('¿Deseas instalar Mobbex Marketplace?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

    }

    /**
     * Install the module on the store
     *
     * @see    Module::install()
     * @todo   bootstrap the configuration requirements of Mobbex
     * @throws PrestaShopException
     * @return bool
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension ') . $this->l('on your server to install this module.');

            return false;
        }

        if (!Module::isInstalled('mobbex') && !Module::isEnabled('mobbex')){
            $this->_errors[] = $this->l('Requires Mobbex Webpay module ') . $this->l('on your server to install this module.');

            return false;
        }

        $this->_createTable();

        return parent::install();
    }

    /**
     * Uninstall the module
     *
     * @see    Module::uninstall()
     * @todo   remove the configuration requirements of Mobbex
     * @throws PrestaShopException
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Register module hooks dependig on prestashop version.
     * 
     * @return bool Result of the registration
     */
    public function registerHooks()
    {
        $hooks = [
            'displayMobbexConfiguration',
            'actionMobbexCheckoutRequest',
            'displayMobbexProductSettings',
            'displayMobbexCategorySettings'
        ];

        $ps16Hooks = [];

        $ps17Hooks = [];

        // Merge current version hooks with common hooks
        $hooks = array_merge($hooks, _PS_VERSION_ > '1.7' ? $ps17Hooks : $ps16Hooks);

        foreach ($hooks as $hookName) {
            if (!$this->registerHook($hookName))
                return false;
        }

        return true;
    }

    /** CONFIG FORM */

    /**
     * Add Marketplace options in Mobbex configuration.
     * @param array $form
     * @return array $form
     */
    public function hookDisplayMobbexConfiguration($form)
    {
        if (Tools::isSubmit('submit_mobbex')) {
            $this->postProcess();
        }
        
        $form['form']['tabs']['tab_marketplace'] = $this->l('Marketplace Configuration');
        $inputs = [
            [
                'type'     => 'switch',
                'label'    => $this->l('Activar Marketplace'),
                'name'     => MarketplaceHelper::K_ACTIVE,
                'is_bool'  => true,
                'required' => false,
                'tab'      => 'tab_marketplace',
                'values'   => [
                    [
                        'id'    => 'active_on_marketplace',
                        'value' => true,
                        'label' => $this->l('Activar'),
                    ],
                    [
                        'id'    => 'active_off_marketplace',
                        'value' => false,
                        'label' => $this->l('Desactivar'),
                    ],
                ],
            ],
            [
                'type'     => 'text',
                'label'    => $this->l('Fee (%)'),
                'name'     => MarketplaceHelper::K_FEE,
                'required' => false,
                'tab'      => 'tab_marketplace',
            ]
        ];

        foreach ($inputs as $value) {
            $form['form']['input'][] = $value;
        }

        return $form;
    }

    /**
     * Logic to apply when the configuration form is posted
     *
     * @return void
     */
    public function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Retrieve the current configuration values.
     *
     * @see $this->renderForm
     *
     * @return array
     */
    protected function getConfigFormValues()
    {
        return array(
            MarketplaceHelper::K_ACTIVE => Configuration::get(MarketplaceHelper::K_ACTIVE, ''),
            MarketplaceHelper::K_FEE    => Configuration::get(MarketplaceHelper::K_FEE, ''),
        );
    }
}