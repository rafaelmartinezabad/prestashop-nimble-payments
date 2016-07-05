<?php
/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *     @author    PrestaShop SA <contact@prestashop.com>
 *     @copyright 2007-2015 PrestaShop SA
 *     @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_CAN_LOAD_FILES_')) {
    exit();
}
if (!defined('_PS_VERSION_')) {
    exit();
}

require_once _PS_MODULE_DIR_ . 'nimblepayment/library/sdk/lib/Nimble/base/NimbleAPI.php';
require_once _PS_MODULE_DIR_ . 'nimblepayment/library/sdk/lib/Nimble/api/NimbleAPIPayments.php';
require_once _PS_MODULE_DIR_ . 'nimblepayment/library/sdk/lib/Nimble/api/NimbleAPICredentials.php';
require_once _PS_MODULE_DIR_ . 'nimblepayment/library/sdk/lib/Nimble/api/NimbleAPIAccount.php';

class NimblePayment extends PaymentModule
{

    public function __construct()
    {
        $this->name = 'nimblepayment';
        $this->tab = 'payments_gateways';
        $this->version = '3.0.0';
        $this->author = 'BBVA';
        $this->bootstrap = true;
        parent::__construct();
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Nimble Payments');
        $this->displayMethod = $this->l('Card Payment');
        $this->description = $this->l('Nimble Payments Gateway');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
        $this->post_errors = array();
    }

    public function install()
    {
        // PHP 5.2
        if (!version_compare(phpversion(), '5.2', '>=')) {
            $this->context->controller->errors[] = $this->l('Nimble Payments module only supports PHP versions greater or equal than 5.2');
            return false;
        }

        // Mapping Nimble Tabs
        $tabs = array(
            'AdminNimbleConfig' => array (
                'label' => $this->l('Nimble Payments'),
                'rootClass' => true,
                )
        );
        // Set tabs for uninstall
        Configuration::updateValue('PS_ADMIN_NIMBLE_TABS', serialize($tabs));

        // Build menu tabs
        foreach ($tabs as $className => $data) {
            // Check if exists
            if (!$id_tab = Tab::getIdFromClassName($className)) {
                if ($data['rootClass']) {
                    $this->installModuleTab($className, $data['label'], 0);
                    $rootClass = $className;
                } else {
                    $this->installModuleTab($className, $data['label'], (int)Tab::getIdFromClassName($rootClass));
                }
            }
        }
        
        if (!parent::install() || ! $this->registerHook('adminOrder') || !$this->registerHook('payment') || !$this->registerHook('paymentReturn') || !$this->registerHook('displayTop')
            || !$this->registerHook('actionAdminLoginControllerSetMedia') || ! $this->registerHook('displayBackOfficeHeader') || ! $this->registerHook('dashboardZoneOne') ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // Unregister hooks
        $this->unregisterHook('adminOrder');
        $this->unregisterHook('payment');
        $this->unregisterHook('paymentReturn');
        $this->unregisterHook('displayTop');
        $this->unregisterHook('dashboardZoneOne');
        $this->unregisterHook('actionAdminControllerSetMedia');
        $this->unregisterHook('displayBackOfficeHeader');
        $tabs = unserialize(Configuration::get('PS_ADMIN_NIMBLE_TABS'));

        // Unbuild Menu
        foreach ($tabs as $className => $data) {
            $this->uninstallModuleTab($className);
        }

        // Remove system variables
        Configuration::deleteByName('PS_ADMIN_NIMBLE_TABS');
        
        if (!Configuration::deleteByName('NIMBLEPAYMENT_CLIENT_ID') || !Configuration::deleteByName('NIMBLEPAYMENT_CLIENT_SECRET')
         || !Configuration::deleteByName('PS_NIMBLE_ACCESS_TOKEN') || !Configuration::deleteByName('PS_NIMBLE_REFRESH_TOKEN') || !Configuration::deleteByName('PS_NIMBLE_CREDENTIALS') || !parent::uninstall()
        ) {
            return false;
        }

        return true;
    }

    /**
     * DisplayBackOfficeHeader Hook implementation
     * @return string html content for back office header
     */
    public function hookDisplayBackOfficeHeader()
    {
        //Add as scoped CSS in back office header
        $this->context->controller->addCSS($this->_path . 'views/css/nimblebackend.css', 'all');
    }
    
    /**
     * DashboardZoneOne Hook implementation
     * @param  array $params hook data
     * @return object         tpl for zone one (top)
     */
    public function hookDashboardZoneOne($params)
    {
        $nimble_credentials = Configuration::get('PS_NIMBLE_CREDENTIALS');
        if (isset($nimble_credentials) && $nimble_credentials == 1) {
            if ( ! Configuration::get('PS_NIMBLE_ACCESS_TOKEN') ){
                $this->context->smarty->assign(
                    array(
                        'data' => "",
                        'Oauth3Url' => $this->getOauth3Url(),
                        'token' => false
                    )
                );
                return $this->display(__FILE__, 'dashboard_zone_one.tpl', '20160617');
            } else {
                try {
                    $params = array(
                        'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
                        'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'),
                        'token' => Configuration::get('PS_NIMBLE_ACCESS_TOKEN')
                    );
                    $nimble = new NimbleAPI($params);
                    $summary = NimbleAPIAccount::balanceSummary($nimble);
                    if ( !isset($summary['result']) || ! isset($summary['result']['code']) || 200 != $summary['result']['code'] || !isset($summary['data'])){
                        //error
                    } else{
                        $totalavailable = $summary['data']['available'] / 100;
                        $total_str = number_format($totalavailable, 2, ',', '.');
                        $balance = $summary['data']['accountBalance'] / 100;
                        $balance_str = number_format($balance, 2, ',', '.');
                        $holdback = $summary['data']['hold'] / 100;
                        $holdback_str = number_format($holdback, 2, ',', '.');
                        
                        $this->context->smarty->assign(
                            array(
                                'data' => "",
                                'token' => true,
                                'total_str' => $total_str,
                                'balance_str' => $balance_str,
                                'holdback_str' => $holdback_str,
                                
                            )
                        );
                        return $this->display(__FILE__, 'dashboard_zone_one.tpl', '20160617');
                    }
                } catch (Exception $e) {
                    //to do
                }
            }         
        }
    }
    
    /**
     * AdminOrder Hook implementation for altering order detail presentation in order to add refund nimble options
     * @param  array $params hook data
     * @return string         HTML output
     */
    public function hookAdminOrder($params)
    {
        $this->_html = "";
        $refunds = array();
        $new_refund_message_class = '';
        $new_refund_message = '';
        $new_refund = Tools::getValue('np_refund', false) ? true : false;
        if ($new_refund){
            $new_refund_message_class = Tools::getValue('np_refund') == 'OK' ? 'success' : 'danger'; 
            $new_refund_message = Tools::getValue('np_refund') == 'OK' ? $this->l('Refund OK') : $this->l('Refund KO'); 
        }
        $refunded = 0;

        // Nimble refund button submitted
        if (Tools::isSubmit('submitNimbleRefund')) {
            $this->_doRefund($params['id_order']);
        }

        // Build tpl addons
        $admin_templates = array();
        // Refund tpl
        $order = new Order((int)$params['id_order']);
        if ($this->_canRefund((int)$params['id_order'])) {
            $transaction = $this->_getIdTransaction($params['id_order']);
            if( !empty($transaction) ){
                $admin_templates[] = 'refund';
                // Set params
                $refunds = $this->getListRefunds($transaction);
            }    
            // Check if total refunds exceed total amount
            if (is_array($refunds)){
                foreach ($refunds as $refund) {
                    $refunded += ($refund['refund']['amount']) / 100 ;
                }
            }
        } else if ($order->module == 'nimblepayment'){
            $admin_templates[] = 'authorize';
        }

        // Get order data
        $order = new Order((int)$params['id_order']);
        $currency = new Currency($order->id_currency);

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $order_state = $order->current_state;
        } else {
            $order_state = OrderHistory::getLastOrderState($params['id_order']);
        }
        
	//error_log( dirname($_SERVER['REQUEST_URI']) . '/' . AdminController::$currentIndex . '&id_order=' . $params['id_order'] . '&vieworder&token=' . Tools::getValue('token'));
        //error_log(_PS_BASE_URL_.__PS_BASE_URI__ . ($_SERVER['REQUEST_URI']) . Context::getContext()->link->getAdminLink('AdminOrders'). '&id_order=' . $params['id_order'] . '&vieworder');
        
	// Set tpl data
        $this->context->smarty->assign(
            array(
                'base_url' => _PS_BASE_URL_.__PS_BASE_URI__,
                'module_name' => $this->name,
                'order_state' => $order_state,
                'params' => $params,
                'id_currency' => $currency->getSign(),
                'list_refunds' => $refunds,
                'still_refundable' => $refunded < (float)$order->total_paid,
                'order_amount' => (float)$order->total_paid,
                'order_currency' => $currency->sign,
                'refunded' => $refunded,
                'description' => $order->reference,
                'ps_version' => _PS_VERSION_,
                'new_refund_message_class' => $new_refund_message_class,
                'new_refund_message' => $new_refund_message,
                'Oauth3Url' => $this->getOauth3Url()
            )
        );

        foreach ($admin_templates as $admin_template) {
            $this->_html .= $this->fetchTemplate('/views/templates/admin/admin_order/'.$admin_template.'.tpl', '20160630');
        }

        return $this->_html;
    }

    
    public function hookActionAdminLoginControllerSetMedia()
    {
        $this->refreshToken();
    }
    
    public function hookDisplayTop()
    {
        $error = Tools::getValue("error");
        if (Tools::getIsset("error") && !empty($error)) {
            return $this->display(__FILE__, 'display_top.tpl', '20160617');
        }
    }

    private function postValidation()
    {
        if (Tools::isSubmit('saveCredentials')) {
            if ($this->checkCredentials() == false) {
                $this->post_errors[] = $this->l('Data invalid gateway to accept payments.');
            }
        }
    }

    private function postProcess()
    {
        if (Tools::isSubmit('saveCredentials')) {
            Configuration::updateValue('NIMBLEPAYMENT_CLIENT_ID', trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_ID')));
            Configuration::updateValue('NIMBLEPAYMENT_CLIENT_SECRET', trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET')));
        }
        return $this->displayConfirmation($this->l('Settings updated'));
    }

    private function displaynimblepayment()
    {
        $url_nimble = $this->getGatewayUrl();
        $this->smarty->assign(
            array(
                'url_nimble' => $url_nimble
            )
        );
        return $this->display(__FILE__, 'infos.tpl', '20160615');
    }

    public function getContent()
    {
        $output = null;
        Configuration::updateValue(
            'NIMBLE_REQUEST_URI_ADMIN',
            dirname($_SERVER['REQUEST_URI']) . '/' . AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules')
        );
        
        if (Tools::isSubmit('removeOauth2')) {
            $this->removeOauthToken();
        }
        
        if (Tools::isSubmit('saveCredentials')) {
            $this->postValidation();
            if (!count($this->post_errors)) {
                $output .= $this->postProcess();
            } else {
                foreach ($this->post_errors as $err) {
                    $output .= $this->displayError($err);
                }
            }
        }
        
        $credentials = Configuration::get('PS_NIMBLE_CREDENTIALS');
        if ( $credentials && ! Configuration::get('PS_NIMBLE_ACCESS_TOKEN') ){
            $output .= $this->authorize3legged();
        }
        
        $output .= $this->displaynimblepayment();
        $output .= '<div id="nimble-form">' . $this->renderForm() . '</div>';
        if ( $credentials && Configuration::get('PS_NIMBLE_ACCESS_TOKEN') ){
            $output .= $this->unauthorize3legged();
        }
        
        return $output;
    }

    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $ssl = Configuration::get('PS_SSL_ENABLED');
        $this->smarty->assign(
            array(
                'ssl' => $ssl,
                'params' => array(),
                'this_path' => $this->_path,
                'this_path_bw' => $this->_path,
                'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
            )
        );
        
        $nimble_credentials = Configuration::get('PS_NIMBLE_CREDENTIALS');
        if (isset($nimble_credentials) && $nimble_credentials == 1) {
            return $this->display(__FILE__, 'payment.tpl', '20160623');
        }
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $state = $params['objOrder']->getCurrentState();
        $reference = $params['objOrder']->reference;
        
        if (in_array($state, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')))) {
            $id_order = (int) Tools::getValue('id_order');
            $order = new Order($id_order);
            if (Validate::isLoadedObject($order)) {
                //customer data
                $transaction = $this->_getIdTransaction($id_order);
                try {
                    $params = array(
                        'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
                        'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET')
                    );

                    $nimbleApi = new NimbleAPI($params);
                    $updateCustomer = NimbleAPIPayments::updateCustomerData($nimbleApi, $transaction, $reference);
                    if ( !isset($updateCustomer['result']) || ! isset($updateCustomer['result']['code']) || 200 != $updateCustomer['result']['code'] || !isset($updateCustomer['info']) || 'OK' != $updateCustomer['info'] ){
                        //to do
                    }
                } catch (Exception $e) {
                    //to do
                }
                
                $order->id_customer = $this->context->customer->id;
                $id_order_state = (int) $order->getCurrentState();
                $carrier = new Carrier((int) $order->id_carrier, (int) $order->id_lang);
                $addressInvoice = new Address((int) $order->id_address_invoice);
                $addressDelivery = new Address((int) $order->id_address_delivery);

                $inv_adr_fields = AddressFormat::getOrderedAddressFields($addressInvoice->id_country);
                $dlv_adr_fields = AddressFormat::getOrderedAddressFields($addressDelivery->id_country);

                $invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressInvoice, $inv_adr_fields);
                $deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressDelivery, $dlv_adr_fields);

                if ($order->total_discounts > 0) {
                    $this->context->smarty->assign('total_old', (float) $order->total_paid - $order->total_discounts);
                }
                $products = $order->getProducts();

                /* DEPRECATED: customizedDatas @since 1.5 */
                $customizedDatas = Product::getAllCustomizedDatas((int) $order->id_cart);
                Product::addCustomizationPrice($products, $customizedDatas);

                OrderReturn::addReturnedQuantity($products, $order->id);
                $order_status = new OrderState((int) $id_order_state, (int) $order->id_lang);

                $customer = new Customer($order->id_customer);
                $price = Tools::displayPrice($order->total_paid, $this->context->currency);
                $this->context->smarty->assign(array(
                    'shop_name' => (string) Configuration::get('PS_SHOP_NAME'),
                    'order' => $order,
                    'price' => $price,
                    'status' => 'ok',
                    'return_allowed' => (int) $order->isReturnable(),
                    'currency' => new Currency($order->id_currency),
                    'order_state' => (int) $id_order_state,
                    'invoiceAllowed' => (int) Configuration::get('PS_INVOICE'),
                    'invoice' => (OrderState::invoiceAvailable($id_order_state) && count($order->getInvoicesCollection())),
                    'logable' => (bool) $order_status->logable,
                    'order_history' => $order->getHistory($this->context->language->id, false, true),
                    'products' => $products,
                    'discounts' => $order->getCartRules(),
                    'carrier' => $carrier,
                    'address_invoice' => $addressInvoice,
                    'invoiceState' => (Validate::isLoadedObject($addressInvoice) && $addressInvoice->id_state) ? new State($addressInvoice->id_state) : false,
                    'address_delivery' => $addressDelivery,
                    'inv_adr_fields' => $inv_adr_fields,
                    'dlv_adr_fields' => $dlv_adr_fields,
                    'invoiceAddressFormatedValues' => $invoiceAddressFormatedValues,
                    'deliveryAddressFormatedValues' => $deliveryAddressFormatedValues,
                    'deliveryState' => (Validate::isLoadedObject($addressDelivery) && $addressDelivery->id_state) ? new State($addressDelivery->id_state) : false,
                    'is_guest' => false,
                    'messages' => CustomerMessage::getMessagesByOrderId((int) $order->id, false),
                    'CUSTOMIZE_FILE' => Product::CUSTOMIZE_FILE,
                    'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
                    'isRecyclable' => Configuration::get('PS_RECYCLABLE_PACK'),
                    'use_tax' => Configuration::get('PS_TAX'),
                    'group_use_tax' => (Group::getPriceDisplayMethod($customer->id_default_group) == PS_TAX_INC),
                    /* DEPRECATED: customizedDatas @since 1.5 */
                    /* 'customizedDatas' => $customizedDatas,
                      /* DEPRECATED: customizedDatas @since 1.5 */
                    'reorderingAllowed' => !(bool) Configuration::get('PS_DISALLOW_HISTORY_REORDERING')
                ));

                if ($carrier->url && $order->shipping_number) {
                    $this->context->smarty->assign('followup', str_replace('@', $order->shipping_number, $carrier->url));
                }
                $this->context->smarty->assign('HOOK_ORDERDETAILDISPLAYED', Hook::exec('displayOrderDetail', array('order' => $order)));
                Hook::exec('actionOrderDetail', array('carrier' => $carrier, 'order' => $order));

                unset($carrier, $addressInvoice, $addressDelivery);
            } else {
                $this->errors[] = Tools::displayError('This order cannot be found.');
            }
            unset($order);
        } else {
            $this->smarty->assign('status', 'failed');
        }
        return $this->display(__FILE__, 'payment_return.tpl', '20160617');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function renderForm()
    {
        $this->fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Client Details'),
                'icon' => 'icon-edit'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Client id'),
                    'name' => 'NIMBLEPAYMENT_CLIENT_ID',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Client secret'),
                    'name' => 'NIMBLEPAYMENT_CLIENT_SECRET',
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'saveCredentials';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm($this->fields_form);
    }

    public function checkCurrencyNimble($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getConfigFieldsValues()
    {
        return array(
            'NIMBLEPAYMENT_CLIENT_ID' => Tools::getValue('NIMBLEPAYMENT_CLIENT_ID', Configuration::get('NIMBLEPAYMENT_CLIENT_ID')),
            'NIMBLEPAYMENT_CLIENT_SECRET' => Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET', Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'))
        );
    }

    public function checkCredentials()
    {
        $validator = false;
        Configuration::updateValue('PS_NIMBLE_CREDENTIALS', 0);

        try {
            $params = array(
                'clientId' => trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_ID')),
                'clientSecret' => trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET'))
            );

            $nimbleApi = new NimbleAPI($params);
            $response = NimbleAPICredentials::check($nimbleApi);
            if (isset($response) && isset($response['result']) && isset($response['result']['code']) && 200 == $response['result']['code']) {
                $validator = true;
                Configuration::updateValue('PS_NIMBLE_CREDENTIALS', 1);
            } else {
                $validator = false;
            }
        } catch (Exception $e) {
            $validator = false;
        }

        return $validator;
    }

    public function getGatewayUrl()
    {
        $platform = 'Prestashop';
        $storeName = Configuration::get('PS_SHOP_NAME');
        $storeURL = _PS_BASE_URL_ . __PS_BASE_URI__;
        $redirectURL = _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/nimblepayment/oauth2callback.php';
        return NimbleAPI::getGatewayUrl($platform, $storeName, $storeURL, $redirectURL);
    }

    public function getVersionPlugin()
    {
        return $this->version;
    }
    
    /**
     * 
     * @return $url to OAUTH 3 step or false
     */
    public function getOauth3Url()
    {
        $validator = false;    
        try {
            $params = array(
            'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
            'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET')
            );
            
            $nimble_api = new NimbleAPI($params);
            $validator = $nimble_api->getOauth3Url();
        } catch (Exception $e) {
            $validator = false;
        }
    
         return $validator;
    }
    
    public function authorize3legged(){
        $this->smarty->assign('Oauth3Url' , $this->getOauth3Url());
        return $this->display(__FILE__, 'authorize.tpl'); 
    }
    
    public function unauthorize3legged(){
        $this->unauthorize_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Comercio vinculado con Nimble Payments'),
                'icon' => 'icon-edit'
            ),
            'submit' => array(
                'title' => $this->l('Desvincular'),
            )
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'removeOauth2';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm($this->unauthorize_form);
    }
    
    /**
     * Perform oAuth after security server callback returns code confirmation through NimbleAPI SDK.
     * We are getting here automatically through oAuth redirect URL from security server (/module/nimblepayment/oauth2callback.php)
     */
    public function nimbleOauth2callback()
    {
        $oauth = 'undefined';

        // Check for errors on URL parameters
        if (Tools::getValue('error')) {
            Logger::addLog('NIMBLE_PAYMENTS. Unknown error or timeout.', 4);
            $oauth = 'error';
        } elseif (Tools::getValue('code')) {
        // Security server redirection has "code" parameter on the URL
            // Perform oAuth
            $params = array(
                    'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
                    'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'),
                    'oauth_code' => Tools::getValue('code')
            );
            $nimble_api = new NimbleAPI($params);

            // Check if NimbleAPI object has properly perform the authorization process
            if ($nimble_api != null && $nimble_api->authorization->isAccessParams()) {
                $oauth = 'success';
                // Set token data on PS variables
                Configuration::updateValue('PS_NIMBLE_ACCESS_TOKEN', $nimble_api->authorization->getAccessToken());
                Configuration::updateValue('PS_NIMBLE_REFRESH_TOKEN', $nimble_api->authorization->getRefreshToken());
            } else {
                $oauth = 'error';
            }
        }

        // After process redirect to module settings page with oauth2callback parameter on URL
        Tools::redirectAdmin(Configuration::get('NIMBLE_REQUEST_URI_ADMIN').'&oauth2callback='.$oauth);
    }
    
    
    public function removeOauthToken()
    {
        Configuration::updateValue('PS_NIMBLE_ACCESS_TOKEN', null);
        Configuration::updateValue('PS_NIMBLE_REFRESH_TOKEN', null);
    }


    public function checkCredentialsUpdate()
    {
        Configuration::updateValue('PS_NIMBLE_CREDENTIALS', 0);

        try {
            $params = array(
                'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
                'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET')
            );

            $nimbleApi = new NimbleAPI($params);
            $response = NimbleAPICredentials::check($nimbleApi);
            if (isset($response) && isset($response['result']) && isset($response['result']['code']) && 200 == $response['result']['code']) {
                Configuration::updateValue('PS_NIMBLE_CREDENTIALS', 1);
            }
        } catch (Exception $e) {
            Configuration::updateValue('PS_NIMBLE_CREDENTIALS', 0);
        }
    }
    
    public function setDisplayName($name)
    {
        $this->displayName = $this->l($name);
    }
    
    public function refreshToken()
    {
        try {
                $params = array(
                    'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
                    'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'),
                    'token' => Configuration::get('PS_NIMBLE_ACCESS_TOKEN'),
                    'refreshToken' => Configuration::get('PS_NIMBLE_REFRESH_TOKEN')
                 );
                $nimble_api = new NimbleAPI($params);
                $options = array(
                    'token' => $nimble_api->authorization->getAccessToken(),
                    'refreshToken' => $nimble_api->authorization->getRefreshToken()
                );
                if ( empty($options['token']) || empty($options['refreshToken']) ){
                    Configuration::deleteByName('PS_NIMBLE_ACCESS_TOKEN');
                    Configuration::deleteByName('PS_NIMBLE_REFRESH_TOKEN');
                } else {
                    Configuration::updateValue('PS_NIMBLE_ACCESS_TOKEN', $options['token']);
                    Configuration::updateValue('PS_NIMBLE_REFRESH_TOKEN', $options['refreshToken']);
                }
        } catch (Exception $e) {
            Configuration::deleteByName('PS_NIMBLE_ACCESS_TOKEN');
            Configuration::deleteByName('PS_NIMBLE_REFRESH_TOKEN');
        }
        
    }
    
    /**************************************************************
     *                    NIMBLE REFUNDS FUNCTIONS                *
     **************************************************************/
   
    /**
     * Check if an order is refundable
     * @param  int $id_order id order
     * @return boolean           wether or not the order is refundable
     */
    private function _canRefund($id_order)
    {
        // Check oAuth
        if (!Configuration::get('PS_NIMBLE_ACCESS_TOKEN')) {
            return false;
        }

        // Check if order exists and is succesfully paid
        if (!(bool)$id_order) {
            return false;
        }

        $order = new Order($id_order);
        return $order->module == 'nimblepayment';
    }

    /**
     * Refund process implementation pre and post execution
     * @param  int $id_order    id order
     * @param  string $description refund description
     * @param  float $amt         amount to refund
     */
    private function _doRefund($id_order)
    {
        // Get order object
        $order = new Order((int)$id_order);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }
        // Get products buyed on order
        //$products = $order->getProducts();
        $currency = new Currency((int)$order->id_currency);
        if (!Validate::isLoadedObject($currency)) {
            $this->_errors[] = $this->l('Not a valid currency');
        }

        if (count($this->_errors)) {
            return false;
        }

        // Execute refund
        $description = Tools::getValue('description');
        $amt = Tools::getValue('amount');
        $transaction = $this->_getIdTransaction($id_order);
        $response = $this->_makeRefund($transaction, $id_order, (float)($amt), $description);

        //OPEN OPT
        if (isset($response['result']) && isset($response['result']['code']) && 428 == $response['result']['code']
                && isset($response['data']) && isset($response['data']['ticket']) && isset($response['data']['token']) ){
            $ticket = $response['data']['ticket'];
            $url_return = $_SERVER['REQUEST_URI'] . Context::getContext()->link->getAdminLink('AdminOrders'). '&id_order=' . $id_order . '&vieworder';
            $stateRefund = ( Tools::getValue("stateRefund") == 'refund' ) ? true : false;
            
            $otp_info = array(
                'action'    =>  'refund',
                'ticket'    =>  $ticket,
                'token'     =>  $response['data']['token'],
                'order_id'  =>  $id_order,
                'description' => $description,
                'amt'       => $amt,
                'transaction' => $transaction,
                'stateRefund' => $stateRefund,
                'url_return' => $url_return
            );
            $refund_info = serialize($otp_info);
            Configuration::updateValue('NIMBLEPAYMENTS_REFUND_INFO', $refund_info);
            $back_url = _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/nimblepayment/oauth2callback.php';
            $url_otp = NimbleAPI::getOTPUrl($ticket, $back_url);
            Tools::redirect($url_otp);
        } else {
            $message = $this->l('There was a problem trying to execute refund, please try again later').'<br>';
        }

        // Add private message
        $this->_addNewPrivateMessage((int)$id_order, $message);

        // Redirect to origin page (order detail page)
        // Tools::redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Add new message to the order record
     * @param int $id_order id order
     * @param string $message  message to record
     */
    public function _addNewPrivateMessage($id_order, $message)
    {
        if (!(bool)$id_order) {
            return false;
        }

        $new_message = new Message();
        $message = strip_tags($message, '<br>');

        if (!Validate::isCleanHtml($message)) {
            $message = $this->l('Payment message is not valid, please check your module.');
        }

        $new_message->message = $message;
        $new_message->id_order = (int)$id_order;
        $new_message->private = 1;

        return $new_message->add();
    }

    /**
     * Perform refund through Nimble API SDK
     * @param  int  $id_transaction id transaction
     * @param  int  $id_order       id order
     * @param  float $amt            amount to refund
     * @param  string  $description    description for refund
     * @return array                 refund API callback response
     */
    private function _makeRefund($id_transaction, $id_order, $amt, $description = "")
    {
        if (!$id_transaction) {
            die(Tools::displayError('Fatal Error: id_transaction is null'));
        }

        $refund_params = array(
                 'amount' => (float)$amt * 100,
                 'concept' => $description,
                 'reason' => 'REQUEST_BY_CUSTOMER',
                );

        $params = array(
                'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
                'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'),
                'token' => Configuration::get('PS_NIMBLE_ACCESS_TOKEN')
            );

        $nimble = new NimbleAPI($params);

        if ($nimble != null && $nimble->authorization->isAccessParams()) {
            // Do refund
            return NimbleAPIPayments::sendPaymentRefund($nimble, $id_transaction, $refund_params);
        } else {
            // Auth problem -> Redirect to module settings page
            
        }
    }

    /**
     * Retrieves list of refunds performed for specified transaction
     * @param  int $IdTransaction id_transaction
     * @return array                list of refunds
     */
    public function getListRefunds($IdTransaction)
    {

        $params = array(
                'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
                'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'),
                'token' => Configuration::get('PS_NIMBLE_ACCESS_TOKEN')
            );

        $nimble = new NimbleAPI($params);

        if ($nimble != null && $nimble->authorization->isAccessParams()) {
            // Do refund
            $payment = NimbleAPIPayments::getPaymentRefunds($nimble, $IdTransaction);
            if (isset($payment['error'])) {
                    return $payment['error'];
            } elseif (isset($payment['data']['refunds'])) {
                return $payment['data']['refunds'];
            }
        } else {
            // Auth problem -> Redirect to module settings page
            return $this->l('There was a problem trying to authenticate with Nimble API');
        }
    }
    
    public function nimbleProcessRefund($refund_info)
    {
        try {
            $params = array(
                'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
                'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'),
                'token' => $refund_info['token']
            );
            $nimble = new NimbleAPI($params);
            
            $refund = array(
                'amount' => $refund_info['amt'] * 100,
                'concept' => $refund_info['description'],
                'reason' => 'REQUEST_BY_CUSTOMER'
            );
            
            $response = NimbleAPIPayments::sendPaymentRefund($nimble, $refund_info['transaction'], $refund);
        } catch (Exception $e) {
            return false;
        }
        if (!isset($response['data']) || !isset($response['data']['refundId'])){
            //LANG: ERROR_REFUND_1
            Tools::redirectAdmin($refund_info['url_return'] . '&np_refund=error#nimble-refund-panel');
        } else {        
            if( $refund_info['stateRefund'] == true ){
                // Register refund on order history and save history
                $history = new OrderHistory();
                $history->id_order = (int)$refund_info['order_id'];
                $history->changeIdOrderState((int)Configuration::get('PS_OS_REFUND'), $history->id_order);
                $history->addWithemail();
                $history->save();
            }
        }   
        
       Tools::redirectAdmin($refund_info['url_return'] . '&np_refund=OK#nimble-refund-panel');
}
    
   /**
     * Retrieves id transaction from id_order
     * @param  int $id_order id order
     * @return int           id transaction
     */
    private function _getIdTransaction($id_order)
    {
            return Db::getInstance()->getValue('
                SELECT `transaction_id`
                FROM `'._DB_PREFIX_.'orders` o
                LEFT JOIN `'._DB_PREFIX_.'order_payment` op ON (o.`reference` = op.`order_reference`)
                WHERE o.`id_order` = '.(int)$id_order);

    }

    /**
     * Retrieves tpl object from name, fetching for the proper path
     * @param  string $name tpl name
     * @return object       tpl display object
     */
    public function fetchTemplate($name, $cache_id = null)
    {
        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            $this->context->smarty->currentTemplate = $name;
        } elseif (version_compare(_PS_VERSION_, '1.5', '<')) {
            return $this->display(__FILE__, $name, $cache_id);
        }
        return $this->display(__FILE__, $name);
    }    
     
    /**
     * PS module tab installation callback implementation
     * @param  string $tabClass    tab class
     * @param  string $tabName     tab name
     * @param  int $idTabParent id tab parent
     * @return bool              wether or not tab was propery installed
     */
    private function installModuleTab($tabClass, $tabName, $idTabParent)
    {
        $o = false;

        // Create tab object
        $tab = new Tab();
        $tab->class_name = $tabClass;
        $tab->id_parent = $idTabParent;
        $tab->module = $this->name;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->l($tabName);
        }

        return $tab->save();

    }
    
     /**
     * PS module tab uninstallation callback implementation
     * @param  string $tabClass    tab class
     * @return bool              wether or not tab was propery uninstalled
     */
    private function uninstallModuleTab($tabClass)
    {

        $idTab = Tab::getIdFromClassName($tabClass);
        if ($idTab != 0) {
            $tab = new Tab($idTab);
            $tab->delete();
            return true;
        }
        return false;
    }

}
