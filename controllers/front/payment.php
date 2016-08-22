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
require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/api/NimbleAPIStoredCards.php';

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
		$this->nimblepayment_client_secret = Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET');
		$this->nimblepayment_client_id = Configuration::get('NIMBLEPAYMENT_CLIENT_ID');
		parent::initContent();
		$cart = $this->context->cart;
		if ($cart->nbProducts() <=0) {
			//Tools::redirect('index.php?controller=order');
			$this->result['redirect'] = 'index.php?controller=order';
		}
		if (!$this->module->checkCurrencyNimble($cart)) {
			//Tools::redirect('index.php?controller=order');
			$this->result['redirect'] = 'index.php?controller=order';
		}

		$total = $cart->getOrderTotal(true, Cart::BOTH) * 100;
		$order_num = str_pad($cart->id, 8, '0', STR_PAD_LEFT);
		$paramurl = $order_num.md5($order_num.$this->nimblepayment_client_secret.$total);
		$storedCard = Tools::getValue('nimblepayment_storedcard');
		if( isset($storedCard) && !empty($storedCard) ){
			$this->storedCardPayment($total, $paramurl, $storedCard);
		} else {
			$this->sendPayment($total, $paramurl);
		}
		if (Tools::getIsset('action')) {
			die(Tools::jsonEncode($this->result));
		}
		Tools::redirect($this->result['redirect']);
	}

	public function storedCardPayment($total, $paramurl, $storedCard)
	{
		$cart = $this->context->cart;
		$userId = $this->context->customer->id;
		$id_address = Address::getFirstCustomerAddressId($userId);
		error_log("id_address: " . $id_address);
		$id_address_delivery = (int) $this->context->cart->id_address_delivery;
		error_log("id_address_delivery: " . $id_address_delivery);
		$id_address_invoice = (int) $this->context->cart->id_address_invoice;
		error_log("id_address_invoice: " . $id_address_invoice);

		try{
			$params = array(
			'clientId' => $this->nimblepayment_client_id,
			'clientSecret' => $this->nimblepayment_client_secret
			);

			$nimbleApi = new NimbleAPI($params);

			//ADD HEADER SOURCE CALLER
			$nimblePayment = new NimblePayment();
			$version = $nimblePayment->getVersionPlugin();
			$nimbleApi->authorization->addHeader('source-caller', 'PRESTASHOP_'.$version);

			$storedCardPaymentInfo = array(
				'amount'			=>	$total,
				'currency'			=>	$this->getCurrency(),
				'merchantOrderId'	=>	$cart->id,
				"cardHolderId"		=>	$userId
			);

			$preorder = NimbleAPIStoredCards::preorderPayment($nimbleApi, $storedCardPaymentInfo);

			if ( isset($preorder["data"]) && isset($preorder["data"]["id"])){
				//Save transaction_id to this order
				$this->context->cookie->__set('nimble_transaction_id', $preorder['data']['id']);
				$response = NimbleAPIStoredCards::confirmPayment($nimbleApi, $preorder["data"]);
				//error_log(print_r($response,true));
				$this->result['redirect'] = $this->context->link->getModuleLink('nimblepayment', 'paymentok', array('paymentcode' => $paramurl));
			} else {
				$this->result['redirect'] = $this->context->link->getModuleLink('nimblepayment', 'paymentko', array('paymentcode' => $paramurl));
			}
		}
		catch (Exception $e) {
			//Error
		}
	}

	public function sendPayment($total, $paramurl)
	{
		$cart = $this->context->cart;
		$userId = $this->context->customer->id;
		try {
			$params = array(
			'clientId' => $this->nimblepayment_client_id,
			'clientSecret' => $this->nimblepayment_client_secret
			);
			$this->nimbleapi = new NimbleAPI($params);

			$payment = array(
				'amount'			=>	$total,
				'currency'			=>	$this->getCurrency(),
				'merchantOrderId'	=>	$cart->id,
				"cardHolderId"		=>	$userId,
				'paymentSuccessUrl' =>	$this->context->link->getModuleLink('nimblepayment', 'paymentok', array('paymentcode' => $paramurl)),
				'paymentErrorUrl'	=>	$this->context->link->getModuleLink('nimblepayment', 'paymentko', array('paymentcode' => $paramurl))
			);

			$this->result['redirect'] = $payment['paymentErrorUrl'];

			//ADD HEADER SOURCE CALLER
			$nimblePayment = new NimblePayment();
			$version = $nimblePayment->getVersionPlugin();
			$this->nimbleapi->authorization->addHeader('source-caller', 'PRESTASHOP_'.$version);
			//ADD LANG
			$this->nimbleapi->changeDefaultLanguage($this->context->language->iso_code);
			$response = NimbleAPIPayments::sendPaymentClient($this->nimbleapi, $payment);

			//Save transaction_id to this order
			if (isset($response["data"]) && isset($response["data"]["id"])) {
				$this->context->cookie->__set('nimble_transaction_id', $response['data']['id']);
			}

			if (!isset($response["data"]) || !isset($response["data"]["paymentUrl"])) {
				$this->result['redirect'] = $this->context->link->getModuleLink('nimblepayment', 'paymentko', array('paymentcode' => $paramurl, 'error_code' => 'ERR_CONEX'));
			} else {
				$this->result['redirect'] = $response['data']['paymentUrl'];
			}
		} catch (Exception $e) {
			$this->result['redirect'] = $this->context->link->getModuleLink('nimblepayment', 'paymentko', array('paymentcode' => $paramurl, 'error_code' => 'ERR_PAG'));
		}
}

	public function getCurrency()
	{
		$cart = $this->context->cart;
		$currency_order = new Currency($cart->id_currency);
		$current_currency = $currency_order->iso_code;

		return $current_currency;

	}

}