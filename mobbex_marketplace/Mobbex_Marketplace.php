<?php

/**
 * mobbex_marketplace.php
 *
 * Main file of the module
 *
 * @author  Mobbex Co <admin@mobbex.com>
 * @version 1.0.0
 * @see     PaymentModuleCore
 */

if (!defined('_PS_VERSION_'))
    exit;

require_once dirname(__FILE__) . '/classes/MarketplaceHelper.php';
require_once dirname(__FILE__) . '/classes/MobbexVendor.php';
require_once dirname(__FILE__) . '/classes/MarketplaceTransaction.php';
require_once _PS_MODULE_DIR_ . 'mobbex/classes/Updater.php';
require_once _PS_MODULE_DIR_ . 'mobbex/classes/MobbexCustomFields.php';
require_once _PS_MODULE_DIR_ . 'mobbex/classes/MobbexHelper.php';

/**
 * Main class of the module
 */
class Mobbex_Marketplace extends Module
{
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
        $this->updater = new \Mobbex\Updater('https://github.com/mobbexco/prestashop-marketplace.git');
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
        $this->_installTab();

        return parent::install() && $this->registerHooks();
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
            'displayMobbexOrderWidget'
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
            PrestaShopLogger::addLog('Mobbex Update Error: ' . $e->getMessage(), 3, null, 'Mobbex', null, true, null);
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
        if (Tools::isSubmit('submit_mobbex')) {
            $this->postProcess();
        }

        if (!empty($_GET['run_mrkt_update'])) {
            $this->runUpdate();
            Tools::redirectAdmin(MobbexHelper::getUpgradeURL());
        }

        if ($this->updater->hasUpdates(MarketplaceHelper::MOBBEX_MARKETPLACE_VERSION))
            $form['form']['description'] = "¡Nueva actualización disponible! Haga <a href='$_SERVER[REQUEST_URI]&run_mrkt_update=1'>clic aquí</a> para actualizar a la versión " . $this->updater->latestRelease['tag_name'];
        
        $form['form']['tabs']['tab_marketplace'] = $this->l('Marketplace Configuration');
        $inputs = [
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
            MarketplaceHelper::K_FEE    => Configuration::get(MarketplaceHelper::K_FEE, ''),
        );
    }

    /** INSTALL VENDOR TABLE METHODS */

    public function _createTable()
    {

        $db = DB::getInstance();

        $db->execute(
            "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "mobbex_vendor` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `tax_id` TEXT NOT NULL,
				`name` TEXT NOT NULL,
				`fee` TEXT NOT NULL,
				`hold` BOOLEAN NOT NULL,
                `created` DATE NOT NULL
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;"
        );
        $db->execute(
            "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "mobbex_marketplace_transaction` (
                `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`payment_id` TEXT NOT NULL,
                `data` TEXT NOT NULL
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;"
        );
    }

    /**
     * Install Mobbex controller in backoffice
     * @return boolean
     */
    protected function _installTab()
    {
        
        $tab = new Tab();
        $tab->class_name = 'AdminMobbex';
        $tab->module     = $this->name;
        $tab->id_parent  = (int)Tab::getIdFromClassName('DEFAULT');
        $tab->icon       = 'settings_applications';
        $languages       = Language::getLanguages();

        foreach ($languages as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Mobbex Admin controller');
        }
        try {
            $tab->save();
        } catch (Exception $e) {
            echo $e->getMessage();
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
                echo $e->getMessage();
                return false;
            }
        }
        return true;
    }

        /**
     * Add Marketplace data to Mobbex checkout body if marketplace is active.
     * 
     * @param array
     * @param array
     * @return array
     */
    public function hookActionMobbexCheckoutRequest($data, $products)
    {

        $vendors = MarketplaceHelper::getProductVendors($products);

        if(!$vendors)
            throw new Exception("One or more products doesn't have a vendor." );

        foreach ($vendors as $vendor_id => $items) {

            $productIds = [];

            foreach ($items as $item) {

                $total        = round($item['price_wt'], 2);
                $fee          = MarketplaceHelper::getProductFee($item['id_product']);
                $productIds[] = $item['id_product'];
                $vendor       = MobbexVendor::getVendors(true, 'id', $vendor_id); 
                $data['split'][] = [
                    'tax_id'      => strval($vendor[0]['tax_id']),
                    'description' => "Split payment - tax_".$vendor[0]['tax_id'].":".$vendor[0]['tax_id']."- Product IDs: " . implode(", ", $productIds),
                    'total'       => $total,
                    'reference'   => $data['reference'] . '_split_' . $vendor[0]['tax_id'],
                    'fee'         => $fee . '%',
                    'hold'        => $vendor[0]['hold'] == 1 ? true : false,
                ];
            }
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
            'vendors'       => MobbexVendor::getVendors() ?: [],
            'currentVendor' => MobbexCustomFields::getCustomField($params['id'], 'product', 'vendor') ?: null,
            'fee'           => MobbexCustomFields::getCustomField($params['id'], 'product', 'fee') ?: '',
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
            'vendors'       => MobbexVendor::getVendors() ?: [],
            'currentVendor' => MobbexCustomFields::getCustomField($params['id'], 'category', 'vendor') ?: null,
            'fee'           => MobbexCustomFields::getCustomField($params['id'], 'category', 'fee') ?: '',
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
                MobbexCustomFields::saveCustomField($params['id_product'], 'product', 'vendor', $vendor);
            if($fee)
                MobbexCustomFields::saveCustomField($params['id_product'], 'product', 'fee', $fee);

        } else {
            // Save data directly
            MobbexCustomFields::saveCustomField($params['id_product'], 'product', 'vendor', $vendor);
            MobbexCustomFields::saveCustomField($params['id_product'], 'product', 'fee', $fee);
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
                MobbexCustomFields::saveCustomField($params['category']->id, 'category', 'vendor', $vendor);
            if($fee)
                MobbexCustomFields::saveCustomField($params['category']->id, 'category', 'fee', $fee);
            } else {
                // Save data directly
                MobbexCustomFields::saveCustomField($params['category']->id, 'category', 'vendor', $vendor);
                MobbexCustomFields::saveCustomField($params['category']->id, 'category', 'fee', $fee);
        }
    }

    /**
     * Show Marketplace info in order Widget.
     * 
     * @param array $params
     */
    public function hookDisplayMobbexOrderWidget($params)
    {
        $data = MarketplaceTransaction::getData($params['id']);
        $data = json_decode($data['data'], true);

        $this->context->smarty->assign([
            'data' => $data
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
    public function hookActionMobbexWebhook($data, $cart_id) {

        //Get items
        $cart = new Cart($cart_id);
        $products = $cart->getProducts();
        $items = MarketplaceHelper::getMarketplaceItems($products, $cart->getOrderTotal(), $data['total']);
        
        //Save the data
        return MarketplaceTransaction::saveTransaction($data['payment_id'], json_encode($items));
    }
    
}

