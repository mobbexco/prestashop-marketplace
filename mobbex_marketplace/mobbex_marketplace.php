<?php

/**
 * mobbex_marketplace.php 
 *
 * Main file of the module
 *
 * @author  Mobbex Co <admin@mobbex.com>
 * @version 1.2.2
 * @see     PaymentModuleCore
 */

if (!defined('_PS_VERSION_'))
    exit;

use \Mobbex\PS\Checkout\Models\Logger;

/**
 * Main class of the module
 */
class Mobbex_Marketplace extends Module
{
    /** @var \Mobbex\PS\Checkout\Models\Updater */
    public $updater;

    /** @var \Db */
    public $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        require_once _PS_MODULE_DIR_ . 'mobbex/Models/Updater.php';
        require_once _PS_MODULE_DIR_ . 'mobbex/Models/Config.php';
        require_once _PS_MODULE_DIR_ . 'mobbex/Models/Logger.php';
        require_once _PS_MODULE_DIR_ . 'mobbex_marketplace/Models/Helper.php';
        require_once _PS_MODULE_DIR_ . 'mobbex_marketplace/Models/Vendor.php';
        require_once _PS_MODULE_DIR_ . 'mobbex_marketplace/Models/Transaction.php';

        $this->name            = 'mobbex_marketplace';
        $this->tab             = 'payments_gateways';
        $this->version         = \Mobbex\PS\Marketplace\Models\Helper::PLUGIN_VERSION;
        $this->author          = 'Mobbex Co';
        $this->currencies_mode = 'checkbox';
        $this->bootstrap       = true;

        parent::__construct();

        $this->displayName            = $this->l('Mobbex Marketplace');
        $this->description            = $this->l('Plugin de marketplace para Mobbex');
        $this->confirmUninstall       = $this->l('¿Deseas instalar Mobbex Marketplace?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        //Mobbex Classes
        $this->updater = new \Mobbex\PS\Checkout\Models\Updater('mobbexco/prestashop-marketplace');
        $this->db      = \Db::getInstance();
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

        $this->_createTables();
        $this->_installTab();

        return parent::install() && $this->unregisterHooks() && $this->registerHooks();
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
        $this->unregisterHooks();
        return parent::uninstall() && $this->_uninstallTab();
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
            'displayMobbexCategorySettings',
            'actionProductUpdate',
            'actionCategoryUpdate',
            'displayMobbexOrderWidget',
            'actionMobbexWebhook',
            'actionGetMobbexProductEntity'
        ];

        foreach ($hooks as $hookName) {
            if (!$this->registerHook($hookName))
                return false;
        }

        return true;
    }

    /**
     * Unregister all current module hooks.
     * 
     * @return bool Result.
     */
    public function unregisterHooks()
    {
        // Get hooks used by module
        $hooks = $this->db->executeS(
            'SELECT DISTINCT(`id_hook`) FROM `' . _DB_PREFIX_ . 'hook_module` WHERE `id_module` = ' . $this->id
        ) ?: [];

        foreach ($hooks as $hook) {
            if (!$this->unregisterHook($hook['id_hook']) || !$this->unregisterExceptions($hook['id_hook']))
                return false;
        }

        return true;
    }

    /**
     * Try to update the module.
     * 
     * @return void 
     */
    public function runUpdate()
    {
        try {
            $this->updater->updateVersion($this, true);
        } catch (\PrestaShopException $e) {
            Logger::log('error','Mobbex Marketplace Update Error: ', $e->getMessage());
        }
    }

    /** CONFIG FORM */

    /**
     * Add Marketplace options in Mobbex configuration.
     * @param array $form
     * @return array $form
     */
    public function hookDisplayMobbexConfiguration($form)
    {
        if (Tools::isSubmit('submit_mobbex'))
            $this->postProcess();

        if (!empty($_GET['run_mrkt_update'])) {
            $this->runUpdate();
            Tools::redirectAdmin(\Mobbex\PS\Checkout\Models\Updater::getUpgradeURL());
        }
        if ($this->updater->hasUpdates(\Mobbex\PS\Marketplace\Models\Helper::PLUGIN_VERSION))
            $form['form']['description'] = "¡Nueva actualización disponible! Haga <a href='$_SERVER[REQUEST_URI]&run_mrkt_update=1'>clic aquí</a> para actualizar a la versión " . $this->updater->latestRelease['tag_name'];
        
        $form['form']['tabs']['tab_marketplace'] = $this->l('Marketplace Configuration');
       
        foreach ($this->getFormInputs() as $value)
            $form['form']['input'][] = $value;

        return $form;
    }

    /**
     * Logic to apply when the configuration form is posted
     *
     * @return void
     */
    public function postProcess()
    {
        foreach ($this->getFormInputs() as $input)
            Configuration::updateValue($input['name'], Tools::getValue($input['name']));
    }

    /**
     * Retrieve an array with marketplace config options inputs.
     *
     * Input example:
     * {
     *  'type'     => string | input type <required>,
     *  'label'    => string | input label <required>,
     *  'name'     => string | input database name <required>,
     *  'key'      => string | snake case key to get config <required>,
     *  'default'  => mixed  | input default value <required>,
     *  'required' => bool   | if input is required, <optional>,
     *  'tab'      => string | name of the input father class <required>,
     *  'values'   => array  | array with options, only for select inputs <optional>,
     *  'desc'     => string | input description <optional>,
     *  'is_bool'  => bool   | if input is bool <optional>,
     *  'class'    => string | input class <optional>,
     * }
     *
     * @return array
     */
    protected function getFormInputs()
    {
        $ver = \Mobbex\PS\Checkout\Models\Config::MODULE_VERSION;

        if (version_compare($ver, '4.4.0', '<'))
            return [];

        return [
            [
                'type'     => 'text',
                'label'    => $this->l('Comisión General', 'config-form'),
                'name'     => 'MOBBEX_MARKETPLACE_FEE',
                'required' => false,
                'tab'      => 'tab_marketplace',
                'default'  => '0',
                'key'      => 'marketplace_fee',
                'desc'     => $this->l('La comisión que se le cobrará a los vendedores, si corresponde. Use el signo % para cobrar en porcentaje. Ej. "10%"')
            ]
        ];
    }

    /** INSTALL VENDOR TABLE METHODS */

    public function _createTables()
    {
        $this->runMigrations();
        $this->db->execute(
            "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "mobbex_vendor` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `uid` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `fee` TEXT NOT NULL,
                `hold` BOOLEAN NOT NULL,
                `updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;"
        );

        $this->db->execute(
            "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "mobbex_marketplace_transaction` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `payment_id` TEXT NOT NULL,
                `operation_type` TEXT NOT NULL,
                `data` TEXT NOT NULL
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;"
        );
    }

    /**
     * Check if the tables need to be updated and run the migrations.
     * 
     * @return bool The result of the migrations
     */
    public function runMigrations()
    {
        $results = [];

        $vendorExists = $this->db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'mobbex_vendor"');
        $trxExists    = $this->db->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . 'mobbex_marketplace_transaction"');

        if ($vendorExists) {
            $columns = $this->db->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'mobbex_vendor`');

            // Add uid column
            if (!in_array('uid', array_column($columns, 'Field')))
                $results[] = $this->db->execute(
                    "ALTER TABLE `" . _DB_PREFIX_ . "mobbex_vendor` ADD `uid` TEXT NOT NULL;"
                );

            // Rename created to updated
            if (in_array('created', array_column($columns, 'Field')))
                $results[] = $this->db->execute(
                    "ALTER TABLE `" . _DB_PREFIX_ . "mobbex_vendor` CHANGE `created` `updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;"
                );

            // Remove tax_id
            if (in_array('tax_id', array_column($columns, 'Field')))
                $results[] = $this->db->execute(
                    "ALTER TABLE `" . _DB_PREFIX_ . "mobbex_vendor` DROP `tax_id`;"
                );
        }

        if ($trxExists) {
            $columns = $this->db->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'mobbex_marketplace_transaction`');

            // Add operation_type column
            if (!in_array('operation_type', array_column($columns, 'Field')))
                $results[] = $this->db->execute(
                    "ALTER TABLE `" . _DB_PREFIX_ . "mobbex_marketplace_transaction` ADD `operation_type` TEXT NOT NULL;"
                );
        }

        return !in_array(false, $results);
    }

    /**
     * Install Mobbex controller in backoffice
     * @return boolean
     */
    protected function _installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminMobbex';
        $tab->active     = 1;
        $tab->module     = $this->name;
        $tab->id_parent  = (int)Tab::getIdFromClassName('SELL');
        $tab->icon       = 'people';

        foreach (Language::getLanguages() as $lang)
            $tab->name[$lang['id_lang']] = $this->l('Vendedores • Mobbex');


        try {
            $tab->save();
        } catch (Exception $e) {
            Logger::log('error', 'Mobbex_Marketplace > _installTab | Error installing Tab', $e->getMessage());
            return false;
        }

        return true;
    }
 
    /**
     * Uninstall Mobbex controller in backoffice
     * @return boolean
     */
    protected function _uninstallTab()
    {
        $idTab = (int)Tab::getIdFromClassName('AdminMobbex');
        if ($idTab) {
            $tab = new Tab($idTab);
            try {
                $tab->delete();
            } catch (Exception $e) {
                Logger::log('error', 'Mobbex_Marketplace > _installTab | Error uninstalling Tab', $e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * Add Marketplace data to Mobbex checkout body if marketplace is active.
     * 
     * @param array $data
     * 
     * @return array $data
     * 
     */
    public function hookActionMobbexCheckoutRequest($data)
    {
        if (in_array(\Configuration::get("MOBBEX_MULTIVENDOR"), ['unified', 'active']))
            return $data;

        // Gets the vendors of each item in the current cart
        $cart    = \Context::getContext()->cart;
        $vendors = \Mobbex\PS\Marketplace\Models\Helper::getCartProducts($cart);

        if (!$vendors)
            throw new Exception("One or more products doesn't have a vendor." );

        foreach ($vendors as $vendor_id => $items) {
            $vendor       = \Mobbex\PS\Marketplace\Models\Vendor::getVendors(true, 'id', $vendor_id);
            $prod_ids     = [];
            $vendor_total = 0;

            foreach ($items as $item) {
                $prod_ids[]   = $item['id_product'];
                $vendor_total += $item['total_wt'];
            }

            $data['split'][] = [
                'fee'         => \Mobbex\PS\Marketplace\Models\Helper::calculateFee($prod_ids[0], $vendor_total),
                'total'       => $vendor_total,
                'entity'      => $vendor['uid'],
                'hold'        => isset($vendor['hold']) && $vendor['hold'] == 1 ? true : false,
                'reference'   => $data['reference'] . '_split_' . $vendor['uid'],
                'description' => "Split payment - $vendor[uid] - Product IDs: " . implode(", ", $prod_ids),
            ];
        }

        return $data;
    }

    /** HOOKS */

    /**
     * Show product admin settings.
     * 
     * @param array $params
     */
    public function hookDisplayMobbexProductSettings($params)
    {
        $this->context->smarty->assign([
            'vendors'       => Mobbex\PS\Marketplace\Models\Vendor::getVendors() ?: [],
            'currentVendor' => Mobbex\PS\Checkout\Models\CustomFields::getCustomField($params['id'], 'product', 'vendor') ?: null,
            'fee'           => Mobbex\PS\Checkout\Models\CustomFields::getCustomField($params['id'], 'product', 'fee') ?: '',
        ]);

        return $this->display(__FILE__, 'views/templates/hook/vendors.tpl');
    }

    /**
     * Show category admin settings.
     * 
     * @param array $params
     */
    public function hookDisplayMobbexCategorySettings($params)
    {
        $this->context->smarty->assign([
            'vendors'       => Mobbex\PS\Marketplace\Models\Vendor::getVendors() ?: [],
            'currentVendor' => Mobbex\PS\Checkout\Models\CustomFields::getCustomField($params['id'], 'category', 'vendor') ?: null,
            'fee'           => Mobbex\PS\Checkout\Models\CustomFields::getCustomField($params['id'], 'category', 'fee') ?: '',
        ]);

        return $this->display(__FILE__, 'views/templates/hook/vendors.tpl');
    }

    /**
     * Update product admin settings.
     * 
     * @param array $params
     */
    public function hookActionProductUpdate($params)
    {
        $vendor = isset($_POST['mbbx_vendor']) ? $_POST['mbbx_vendor'] : null;
        $fee    = isset($_POST['mbbx_fee']) ? $_POST['mbbx_fee'] : null;
        // If is bulk import
        if (strnatcasecmp(Tools::getValue('controller'), 'adminImport') === 0) {
            // Only save when they are not empty
            if($vendor)
                Mobbex\PS\Checkout\Models\CustomFields::saveCustomField($params['id_product'], 'product', 'vendor', $vendor);
            if($fee)
                Mobbex\PS\Checkout\Models\CustomFields::saveCustomField($params['id_product'], 'product', 'fee', $fee);

        } else {
            // Save data directly
            Mobbex\PS\Checkout\Models\CustomFields::saveCustomField($params['id_product'], 'product', 'vendor', $vendor);
            Mobbex\PS\Checkout\Models\CustomFields::saveCustomField($params['id_product'], 'product', 'fee', $fee);
        }
    }

    /**
     * Update category admin settings.
     * 
     * @param array $params
     */
    public function hookActionCategoryUpdate($params)
    {
        
        $vendor = isset($_POST['mbbx_vendor']) ? $_POST['mbbx_vendor'] : null;
        $fee    = isset($_POST['mbbx_fee']) ? $_POST['mbbx_fee'] : null;
        
        // If is bulk import
        if (strnatcasecmp(Tools::getValue('controller'), 'adminImport') === 0) {
            // Only save when they are not empty
            if($vendor)
                Mobbex\PS\Checkout\Models\CustomFields::saveCustomField($params['category']->id, 'category', 'vendor', $vendor);
            if($fee)
                Mobbex\PS\Checkout\Models\CustomFields::saveCustomField($params['category']->id, 'category', 'fee', $fee);
            } else {
                // Save data directly
                Mobbex\PS\Checkout\Models\CustomFields::saveCustomField($params['category']->id, 'category', 'vendor', $vendor);
                Mobbex\PS\Checkout\Models\CustomFields::saveCustomField($params['category']->id, 'category', 'fee', $fee);
        }
    }

    /**
     * Show Marketplace info in order Widget.
     * 
     * @param array $params
     */
    public function hookDisplayMobbexOrderWidget($params)
    {
        $trx = \Mobbex\PS\Marketplace\Models\Transaction::getTrx($params['id']);

        if(!$trx || (isset($trx['operation_type']) && $trx['operation_type'] === 'payment.v2'))
            return;

        $this->context->smarty->assign([
            'op_type' => isset($trx['operation_type']) ? str_replace('payment.', '', $trx['operation_type']) : 'split-hybrid',
            'items'   => isset($trx['data']) ? json_decode($trx['data'], true) : []
        ]);

        return $this->display(__FILE__, 'views/templates/hook/widget.tpl');
    }

    /**
     * Create & store Marketplace data from webhook.
     * 
     * @param array
     * @param string
     * 
     */
    public function hookActionMobbexWebhook($data, $cart_id)
    {
        // Try to decode (for checkout < 4.4.0)
        if (is_string($data))
            $data = json_decode($data, true);

        if (!isset($data['payment']['operation']['type']))
            throw new Exception("Error parsing webhook data on marketplace plugin.");

        if ($data['payment']['operation']['type'] === 'payment.v2' || \Mobbex\PS\Marketplace\Models\Transaction::getTrx($data['payment']['id']))
            return;

        //Get items
        $cart     = new Cart($cart_id);
        $products = $cart->getProducts();
        $items    = \Mobbex\PS\Marketplace\Models\Helper::getMarketplaceItems($products, $cart->getOrderTotal(), $data['checkout']['total'], $data['payment']['operation']['type']);
        
        //Save the data
        return \Mobbex\PS\Marketplace\Models\Transaction::saveTransaction($data['payment']['id'], $data['payment']['operation']['type'], json_encode($items));
    }

    public function hookActionGetMobbexProductEntity($product)
    {
        if (!in_array(\Configuration::get("MOBBEX_MULTIVENDOR"), ['unified', 'active']))
            return null;

        $vendorId = \Mobbex\PS\Checkout\Models\CustomFields::getCustomField($product->id, 'product', 'vendor');
        $vendor   = \Mobbex\PS\Marketplace\Models\Vendor::getVendors(true, 'id', $vendorId);

        return $vendor ? $vendor['uid'] : '';
    } 
}