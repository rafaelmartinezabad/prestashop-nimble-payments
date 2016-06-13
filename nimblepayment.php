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

require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/base/NimbleAPI.php';
require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/api/NimbleAPIPayments.php';
require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/api/NimbleAPICredentials.php';

if (! defined('_CAN_LOAD_FILES_')) {
    exit();
}
if (! defined('_PS_VERSION_')) {
    exit();
}

class NimblePayment extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'nimblepayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.1';
        $this->author = 'BBVA';
        $this->bootstrap = true;        
        parent::__construct();
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Nimble Payments');
        $this->description = $this->l('Nimble Payments Gateway');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
        $this->post_errors = array();
    }

    public function install()
    {
        // PHP 5.2
        if (! version_compare(phpversion(), '5.2', '>=')) {
            $this->context->controller->errors[] = $this->l('Nimble Payments module only supports PHP versions greater or equal than 5.2');
            return false;
        }

        if (! parent::install()
            || ! $this->registerHook('payment')
            || ! $this->registerHook('paymentReturn')
            || ! $this->registerHook('actionOrderStatusPostUpdate')
            || ! $this->registerHook('DisplayTop')    
        ) {
            return false;
        }
        
        $this->createOrderState('PENDING_NIMBLE','pending_nimble');
        return true;
    }

    public function uninstall()
    {
        if (!Configuration::deleteByName('NIMBLEPAYMENT_CLIENT_ID')
            || !Configuration::deleteByName('NIMBLEPAYMENT_CLIENT_SECRET')
            || $this->deleteOrderState(Configuration::get('PENDING_NIMBLE'))
            || !Configuration::deleteByName('PENDING_NIMBLE')
            || !Configuration::deleteByName('NIMBLE_REQUEST_URI_ADMIN')
            || !Configuration::deleteByName('PS_NIMBLE_ACCESS_TOKEN')
            || !Configuration::deleteByName('PS_NIMBLE_REFRESH_TOKEN')
            || !parent::uninstall()
        ) {
            return false;
        }

        return true;
    }   
    
    public function hookDisplayTop()
    {
        if(isset($_GET["error"]) && ! empty($_GET["error"]))
            return $this->display(__FILE__, 'display_top.tpl');
    }
    
    public function hookActionOrderStatusPostUpdate($params)
    {
       $transaction_id =$this->context->cookie->nimble_transaction_id;
       $id_order = $params['id_order'];
       if(! empty($transaction_id) && ! empty($id_order))
           $this->save_order_transaction_id($id_order, $transaction_id);
       
       //reset cookie
       $this->context->cookie->__set('nimble_transaction_id', ''); 
    }
    
    public function save_order_transaction_id($id_order, $transaction_id)
    {
        $order = new Order($id_order);
        $collection = OrderPayment::getByOrderReference($order->reference);

        if (count($collection) > 0)
        {
                $order_payment = $collection[0];
                // for older versions (1.5) , we check if it hasn't been filled yet.
                if (!$order_payment->transaction_id)
                {
                        $order_payment->transaction_id = $transaction_id;
                        $order_payment->update();
                }
        }
    }
    
    public function deleteOrderState($id_order_state) 
    {
        $orderState = new OrderState($id_order_state);        
        $orderState->delete();
    }

    private function createOrderState($db_name, $name)
    {
        if (!Configuration::get($db_name))//if status does not exist
        {
            $orderState = new OrderState();
            $orderState->name =  array_fill(0,10,$name);
            $orderState->send_email = false;
            $orderState->color = 'royalblue';
            $orderState->hidden = false;
            $orderState->delivery = false;
            $orderState->logable = false;
            $orderState->invoice = false;
            if ($orderState->add())//save new order status
            {
                $source = _PS_MODULE_DIR_.'/../img/os/'.(int)Configuration::get('PS_OS_BANKWIRE').'.gif';
                $destination = _PS_MODULE_DIR_.'/../img/os/'.(int)$orderState->id.'.gif';
                copy($source, $destination);
                
                Configuration::updateValue($db_name, (int)$orderState->id);
            }
        }
    }
    
    private function postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if ($this->check_credentials() == false){
                $this->post_errors[] = $this->l('Data invalid gateway to accept payments.');
            }
        }
    }

    private function postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('NIMBLEPAYMENT_CLIENT_ID', trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_ID')));
            Configuration::updateValue('NIMBLEPAYMENT_CLIENT_SECRET', trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET')));
        }
        return $this->displayConfirmation($this->l('Settings updated'));
    }

    private function displaynimblepayment()
    {
        $url_nimble = $this->get_gateway_url();
        $this->smarty->assign(
                array(
                'url_nimble' => $url_nimble,
                //'client'     => trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_ID'))   
                )
            );
        return $this->display(__FILE__, 'infos.tpl');
    }

    public function getContent()
    {
        $output = null;

        Configuration::updateValue('NIMBLE_REQUEST_URI_ADMIN', dirname($_SERVER['REQUEST_URI']).'/'.
          AdminController::$currentIndex.'&configure='. $this->name.'&token='.Tools::getAdminTokenLite('AdminModules'));
        
        if (Tools::isSubmit('btnSubmit')) {
            $this->postValidation();
            if (!count($this->post_errors)) {
                $output .= $this->postProcess();
            } else {
                foreach ($this->post_errors as $err) {
                    $output .= $this->displayError($err);
                }
            }
        }
        if ($this->check_credentials() == true && ! Configuration::get('PS_NIMBLE_ACCESS_TOKEN'))
            $output .= $this->authorize3legged();
        $output .= $this->displaynimblepayment();
        $output .= '<div id="nimble-form">'.$this->renderForm().'</div>';
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
            $this->smarty->assign(
                array(
                'this_path' => $this->_path,
                'this_path_bw' => $this->_path,
                'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
                )
            );
            
            return $this->display(__FILE__, 'payment.tpl');    
    }
    
    public function hookPaymentReturn($params)
    {
        if (!$this->active)
                return;

        $state = $params['objOrder']->getCurrentState();
        if (in_array($state, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
        {
            $id_order = (int)Tools::getValue('id_order');
            $order = new Order($id_order);
            if (Validate::isLoadedObject($order)){
                $order->id_customer = $this->context->customer->id;
                $id_order_state = (int)$order->getCurrentState();
                $carrier = new Carrier((int)$order->id_carrier, (int)$order->id_lang);
                $addressInvoice = new Address((int)$order->id_address_invoice);
                $addressDelivery = new Address((int)$order->id_address_delivery);

                $inv_adr_fields = AddressFormat::getOrderedAddressFields($addressInvoice->id_country);
                $dlv_adr_fields = AddressFormat::getOrderedAddressFields($addressDelivery->id_country);

                $invoiceAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressInvoice, $inv_adr_fields);
                $deliveryAddressFormatedValues = AddressFormat::getFormattedAddressFieldsValues($addressDelivery, $dlv_adr_fields);

                if ($order->total_discounts > 0) {
                    $this->context->smarty->assign('total_old', (float)$order->total_paid - $order->total_discounts);
                }
                $products = $order->getProducts();

                /* DEPRECATED: customizedDatas @since 1.5 */
                $customizedDatas = Product::getAllCustomizedDatas((int)$order->id_cart);
                Product::addCustomizationPrice($products, $customizedDatas);

                OrderReturn::addReturnedQuantity($products, $order->id);
                $order_status = new OrderState((int)$id_order_state, (int)$order->id_lang);

                $customer = new Customer($order->id_customer);
                $this->context->smarty->assign(array(
                'shop_name' => strval(Configuration::get('PS_SHOP_NAME')),
                'order' => $order,
                'status' => 'ok',
                'return_allowed' => (int)$order->isReturnable(),
                'currency' => new Currency($order->id_currency),
                'order_state' => (int)$id_order_state,
                'invoiceAllowed' => (int)Configuration::get('PS_INVOICE'),
                'invoice' => (OrderState::invoiceAvailable($id_order_state) && count($order->getInvoicesCollection())),
                'logable' => (bool)$order_status->logable,
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
                'messages' => CustomerMessage::getMessagesByOrderId((int)$order->id, false),
                'CUSTOMIZE_FILE' => Product::CUSTOMIZE_FILE,
                'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
                'isRecyclable' => Configuration::get('PS_RECYCLABLE_PACK'),
                'use_tax' => Configuration::get('PS_TAX'),
                'group_use_tax' => (Group::getPriceDisplayMethod($customer->id_default_group) == PS_TAX_INC),
                /* DEPRECATED: customizedDatas @since 1.5 */
                /*'customizedDatas' => $customizedDatas,
                /* DEPRECATED: customizedDatas @since 1.5 */
                'reorderingAllowed' => !(bool)Configuration::get('PS_DISALLOW_HISTORY_REORDERING')
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
        }

        else
                $this->smarty->assign('status', 'failed');
        return $this->display(__FILE__, 'payment_return.tpl');
            
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
        $this->fields_form[0]['form'] = array (
            'legend' => array(
            'title'  => $this->l('Client Details'),
            'icon'   => 'icon-edit'
            ),
            'input' => array(
                array(
                 'type'  => 'text',
                 'label' => $this->l('Client id'),
                 'name'  => 'NIMBLEPAYMENT_CLIENT_ID',
                ),
                array(
                 'type'  => 'text',
                 'label' => $this->l('Client secret'),
                 'name'  => 'NIMBLEPAYMENT_CLIENT_SECRET',
                )
            ),
            'submit' => array(
         'title' => $this->l('Save'),
        )
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
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
        'NIMBLEPAYMENT_CLIENT_ID'     => Tools::getValue('NIMBLEPAYMENT_CLIENT_ID', Configuration::get('NIMBLEPAYMENT_CLIENT_ID')),
        'NIMBLEPAYMENT_CLIENT_SECRET' => Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET', Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'))
        );
    }
     
    public function check_credentials()
    {    
        $validator = false;

        try {
            $params = array(
            'clientId' => trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_ID')),
            'clientSecret' => trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET'))
            );
            
            $nimbleApi = new NimbleAPI($params);
            $response = NimbleAPICredentials::check($nimbleApi);
            if ( isset($response) && isset($response['result']) && isset($response['result']['code']) && 200 == $response['result']['code'] ){
                $validator = true;
            } else{
                $validator = false;
            }
        } catch (Exception $e) {
            $validator = false;
        }

        return $validator;
    }
    
    public function get_gateway_url()
    {
        $platform = 'Prestashop'; 
        $storeName = Configuration::get('PS_SHOP_NAME');
        $storeURL = _PS_BASE_URL_.__PS_BASE_URI__;
        $redirectURL = _PS_BASE_URL_.__PS_BASE_URI__.'modules/nimblepayment/oauth2callback.php';
        
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
            'clientId' => trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_ID')),
            'clientSecret' => trim(Tools::getValue('NIMBLEPAYMENT_CLIENT_SECRET'))
            );
            
            $nimbleApi = new NimbleAPI($params);
            $validator = $nimbleApi->getOauth3Url();
        } catch (Exception $e) {
            $validator = false;
        }
    
         return $validator;
    }
    
    public function authorize3legged(){
        $this->smarty->assign('Oauth3Url' , $this->getOauth3Url());
        return $this->display(__FILE__, 'authorize.tpl'); 
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
            $NimbleApi = new NimbleAPI($params);

            // Check if NimbleAPI object has properly perform the authorization process
            if ($NimbleApi != null && $NimbleApi->authorization->isAccessParams()) {
                $oauth = 'success';
                // Set token data on PS variables
                Configuration::updateValue('PS_NIMBLE_ACCESS_TOKEN', $NimbleApi->authorization->getAccessToken());
                Configuration::updateValue('PS_NIMBLE_REFRESH_TOKEN', $NimbleApi->authorization->getRefreshToken());
            } else {
                $oauth = 'error';
            }
        }

        // After process redirect to module settings page with oauth2callback parameter on URL
        Tools::redirectAdmin(Configuration::get('NIMBLE_REQUEST_URI_ADMIN').'&oauth2callback='.$oauth);
    }

}
