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

/**
 * error_reporting(E_ALL);
 * ini_set('display_errors', 1);
 */

class NimblePaymentPaymentKoModuleFrontController extends ModuleFrontController
{
    public $nimblepayment_client_secret = '';
    /**
     * @see FrontController::initContent()
     */

    public function initContent()
    {
        parent::initContent();
        $order = Tools::getValue('order');
        $cart_id             = (int)$order['cart_id'];
        $nimble_id           = (int)$order['nimble_id'];
        $nimble_currentOrder = (int)$order['nimble_currentOrder'];
        $customer_key        = $order['customer_key'];
        
        $code = Tools::getValue('paymentcode');
        $cart = (int)Tools::substr($code, 0, 8);
        
        $this->nimblepayment_client_secret = Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET');
        $cart = new Cart($cart);
        $order_num = Tools::substr($code, 0, 8);
        $total_url = $cart->getOrderTotal(true, Cart::BOTH) * 100;
        $paramurl = $order_num.md5($order_num.$this->nimblepayment_client_secret.$total_url);
 
        if ($paramurl == $code) {
            $objOrder = $nimble_currentOrder;
            $history = new OrderHistory();
            $history->id_order = (int)$objOrder;
            $history->changeIdOrderState((int)(Configuration::get('PS_OS_CANCELED')), (int)($objOrder));
            $history->save();

            $oldCart = new Cart(Order::getCartIdStatic($nimble_currentOrder, $this->context->customer->id));
            $duplication = $oldCart->duplicate();
            if (!$duplication || !Validate::isLoadedObject($duplication['cart'])) {
                Tools::displayError('Sorry. We cannot renew your order.');
            } elseif (!$duplication['success']) {
                Tools::displayError('Some items are no longer available, and we are unable to renew your order.');
            } else {
                $this->context->cookie->id_cart = $duplication['cart']->id;
                $context = $this->context;
                $context->cart = $duplication['cart'];
                CartRule::autoAddToCart($context);
                $this->context->cookie->write();
                
                if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                    Tools::redirect('index.php?controller=order-opc');
                }
                Tools::redirect('index.php?controller=order');
                //$this->display(__FILE__, 'shopping-cart1.tpl');
            }
        }
    }
}
