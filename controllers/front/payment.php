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

require_once dirname(__FILE__).'/../../library/sdk/lib/Nimble/base/NimbleAPI.php';
require_once dirname(__FILE__).'/../../library/sdk/lib/Nimble/api/Payments.php';

class NimblePaymentPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $nimblepayment_client_secret = '';
    public $nimblepayment_client_id = '';
    public $nimblepayment_urltpv = '';
    public $type_error = 0;
    public $nimbleapi;

    /**
    * @see FrontController::initContent()
    */
    public function initContent()
    {
        parent::initContent();
        $cart = $this->context->cart;
        if (!$this->module->checkCurrencyNimble($cart)) {
            Tools::redirect('index.php?controller=order');
        }
        if ($this->validatePaymentData() == true) {
            $total = $cart->getOrderTotal(true, Cart::BOTH) * 100;
            $order_num = str_pad($cart->id, 8, '0', STR_PAD_LEFT);
            $paramurl = $order_num.md5($order_num.$this->nimblepayment_client_secret.$total);
            if ($this->authentified() == true) {
                $this->sendPayment($total, $paramurl);
            }
        }
        $this->context->smarty->assign(
            array(
            'this_path' => $this->module->getPathUri(),
            'error' => $this->type_error,
            )
        );
    }

    public function validatePaymentData()
    {
        $this->nimblepayment_client_secret = Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET');
        $this->nimblepayment_client_id = Configuration::get('NIMBLEPAYMENT_CLIENT_ID');
        $this->nimblepayment_urltpv = Configuration::get('NIMBLEPAYMENT_URLTPV');

        if ($this->nimblepayment_client_secret == '' || $this->nimblepayment_client_id == '') {
            $this->setTemplate('payment_failed.tpl');

            //type error = 1
            //show error to the user
            $this->type_error = $this->module->l('Is not possible to contact Nimble Payments. Sorry for the inconvenience.');

            //write in log
            Logger::addLog('NIMBLE_PAYMENTS. Client ID and/or Client secret is empty', 4);

            return false;
        }
        return true;
    }

    public function authentified()
    {
        $params = array(
        'clientId' => $this->nimblepayment_client_id,
        'clientSecret' => $this->nimblepayment_client_secret,
        'mode' => $this->nimblepayment_urltpv
        );

        try {
            $this->nimbleapi = new NimbleAPI($params);
        } catch (Exception $e) {
        //type error = 2
            //$this->type_error = $e->getMessage(); //don´t show that to final user
            $this->type_error = $this->module->l(
                'Is not possible to contact Nimble Payments. There are authentication problems.
			 Sorry for the inconvenience.'
            );
            Logger::addLog('NIMBLE_PAYMENTS. Authentication problems (oAuth)', 4);
            $this->setTemplate('payment_failed.tpl');
            return false;
        }
        return true;
    }

    public function sendPayment($total, $paramurl)
    {
        $cart = $this->context->cart;

        $payment = array(
        'amount' => $total,
        'currency' => 'EUR',
        'customerData' => $cart->id,
        'paymentSuccessUrl' => $this->context->link->getModuleLink('nimblepayment', 'paymentok', array('paymentcode' => $paramurl)),
        'paymentErrorUrl' => $this->context->link->getModuleLink('nimblepayment', 'paymentko', array('paymentcode' => $paramurl))
        );

        try {
            $p = new Payments();
            $response = $p->SendPaymentClient($this->nimbleapi, $payment);
        } catch (Exception $e) {
        //type error = 3 // problem to send payment
            $this->type_error = $this->module->l('Is not possible to send the payment to Nimble Payments. Sorry for the inconvenience.');
            Logger::addLog('NIMBLE_PAYMENTS. Is not possible to send the payment.', 4);
            $this->setTemplate('payment_failed.tpl');
            return false;
        }

        if (empty($response)) {
            //type error = 6
            $this->type_error = $this->module->l('Unknown error or timeout. Sorry for the inconvenience.');
            Logger::addLog('NIMBLE_PAYMENTS. Unknown error or timeout.', 4);
            $this->setTemplate('payment_failed.tpl');
        } elseif (!isset($response['error'])) {
            if ($response['result']['code'] == 200) {
                //save transaction_id in session. After in validateOrder (paymentok.php) we will use transaction_id
                $this->context->cookie->__set('nimble_transaction_id', $response['data']['id']);

                //Tools::redirect($response['data'][0]['paymentUrl']); //old version
                Tools::redirect($response['data']['paymentUrl']);
            } elseif ($response['result']['code'] == 401) {
                //type error = 7 // 401 unauthorized
                $this->setTemplate('payment_failed.tpl');
                $this->type_error = $this->module->l(
                    'Unauthorized access. Please an administrator user should check the credentials 
					and the selected environment to access Nimble Payments. Sorry for the inconvenience.'
                );
                Logger::addLog(
                    'NIMBLE_PAYMENTS. Unauthorized access. Please an administrator user should check the credentials 
					and the selected environment to access Nimble Payments. (Code Error: '.$response['result']['code'].')',
                    4
                );
            } else {
                //type error = 4 // problem to send payment 2
                $this->setTemplate('payment_failed.tpl');
                $this->type_error = $this->module->l(
                    'Is not possible send to the payment to Nimble Payments. Sorry for the inconvenience.
				 Code Error: '
                ).$response['result']['code'];
                Logger::addLog('NIMBLE_PAYMENTS. Is not possible send to the payment to Nimble Payments (Code Error: '.$response['result']['code'].')', 4);
            }
        } else {
            //type error = 5 // problem to send payment 3
            $this->type_error = $this->module->l('We have received an error from Nimble Payments. Sorry for the inconvenience. Error: ')
            .$response['error'];
            $this->setTemplate('payment_failed.tpl');
            Logger::addLog('NIMBLE_PAYMENTS. We have received an error from Nimble Payments (Error: '.$response['error'].')', 4);
        }
    }
}
