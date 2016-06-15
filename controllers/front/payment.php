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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/base/NimbleAPI.php';
require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/api/NimbleAPIPayments.php';
require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/base/NimbleAPIAuthorization.php';

class NimblePaymentPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $nimblepayment_client_secret = '';
    public $nimblepayment_client_id = '';
    public $type_error = 0;
    public $nimbleapi;

    /**
    * @see FrontController::initContent()
    */
    public function initContent()
    {
        $this->result = array();
        parent::initContent();
        $cart = $this->context->cart;
        if($cart->nbProducts() <=0){
            //Tools::redirect('index.php?controller=order');
            $this->result['redirect'] = 'index.php?controller=order';
        }
        if (!$this->module->checkCurrencyNimble($cart)) {
            //Tools::redirect('index.php?controller=order');
            $this->result['redirect'] = 'index.php?controller=order';
        }
        if ($this->validatePaymentData() == true) {
            $total = $cart->getOrderTotal(true, Cart::BOTH) * 100;
            $order_num = str_pad($cart->id, 8, '0', STR_PAD_LEFT);
            $paramurl = $order_num.md5($order_num.$this->nimblepayment_client_secret.$total);
            $this->sendPayment($total, $paramurl);
        }
        
        die( Tools::jsonEncode( $this->result ) );
    }

    public function validatePaymentData()
    {
        $this->nimblepayment_client_secret = Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET');
        $this->nimblepayment_client_id = Configuration::get('NIMBLEPAYMENT_CLIENT_ID');

        if ($this->nimblepayment_client_secret == '' || $this->nimblepayment_client_id == '') {
            $this->setTemplate('payment_failed.tpl');

            //type error = 1
            //show error to the user
            $this->type_error = $this->module->l('Is not possible to contact Nimble Payments. Sorry for the inconvenience.', 'payment');

            //write in log
            Logger::addLog('NIMBLE_PAYMENTS. Client ID and/or Client secret is empty', 4);

            return false;
        }
        return true;
    }

    public function createOrder()
    {
        $order = array();
        $cart = $this->context->cart;
        $status_order = Configuration::get('PENDING_NIMBLE');

        $total = $cart->getOrderTotal(true, Cart::BOTH);
        $extra_vars = array();
        $nimble = new NimblePayment();
        $nimble->validateOrder(
            $cart->id,
            $status_order,
            $total,
            $nimble->displayName,
            null,
            $extra_vars,
            null,
            false,
            $cart->secure_key
        );
        $customer = new Customer($cart->id_customer);

        $order['cart_id'] = $cart->id;
        $order['nimble_id'] = $nimble->id;
        $order['nimble_currentOrder'] = $nimble->currentOrder;
        $order['customer_key'] = $customer->secure_key;            
            
        return $order;
    }        
    
    public function sendPayment($total, $paramurl)
    {
        try {
            $params = array(
            'clientId' => $this->nimblepayment_client_id,
            'clientSecret' => $this->nimblepayment_client_secret
            );
            
            $this->nimbleapi = new NimbleAPI($params);
            
            //Create Order
            $order = array();
            $order = $this->createOrder();

            $payment = array(
                'amount' => $total,
                'currency' => $this->getCurrency(),
                'merchantOrderId' => $order['nimble_currentOrder'],
                'paymentSuccessUrl' => $this->context->link->getModuleLink('nimblepayment', 'paymentok', array('paymentcode' => $paramurl, 'order' => $order)),
                'paymentErrorUrl' => $this->context->link->getModuleLink('nimblepayment', 'paymentko', array('paymentcode' => $paramurl, 'order' => $order))
            );
            
            $this->result['redirect'] = $payment['paymentErrorUrl'];
                    
            //ADD HEADER SOURCE CALLER
            $nimblePayment = new NimblePayment();
            $version = $nimblePayment->getVersionPlugin();
            $this->nimbleapi->authorization->addHeader('source-caller', 'PRESTASHOP_'.$version);
            
            $response = NimbleAPIPayments::sendPaymentClient($this->nimbleapi, $payment);
            
            //Save transaction_id to this order
            if ( isset($response["data"]) && isset($response["data"]["id"])){
                $this->context->cookie->__set('nimble_transaction_id', $response['data']['id']);
            }

            if (!isset($response["data"]) || !isset($response["data"]["paymentUrl"])){
                $this->result['error'] = array(
                    'message' => $this->module->l( 'Unable to process payment. An error has occurred. ERR_CONEX code. Please try later.', 'payment')
                    );
            } else{
                $this->result['redirect'] = $response['data']['paymentUrl'];
            }
        } catch (Exception $e) {
            $this->result['error'] = array(
                'message' => $this->module->l( 'Unable to process payment. An error has occurred. ERR_PAG code. Please try later.', 'payment')
                );
        }
    }

    public function getCurrency(){
        $cart = $this->context->cart;
        $currency_order = new Currency($cart->id_currency);
        $current_currency = $currency_order->iso_code;
        
        return $current_currency;
    }
}
