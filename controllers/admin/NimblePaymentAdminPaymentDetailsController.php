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

class NimblePaymentAdminPaymentDetailsController extends ModuleAdminController
{
    public function ajaxProcesspaymentDetails()
    {
        $nimblePayment = new NimblePayment();
        $authorizeUrl = $nimblePayment->getAurhotizeUrl();
        $this->context->smarty->assign(array(
            'Oauth3Url'       => $authorizeUrl,
        ));
        $template = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'nimblepayment/views/templates/admin/admin_order/authorize.tpl');
   
        if (Tools::getIsset('order_id')){
            $order_id = Tools::getValue('order_id');
            
            $nimblePayment = new NimblePayment();
            $transaction_id = $nimblePayment->_getIdTransaction($order_id);

            try {
                $params = array(
                    'clientId' => Configuration::get('NIMBLEPAYMENT_CLIENT_ID'),
                    'clientSecret' => Configuration::get('NIMBLEPAYMENT_CLIENT_SECRET'),
                    'token' => Configuration::get('PS_NIMBLE_ACCESS_TOKEN')
                 );
                
                $nimble_api = new NimbleAPI($params);
                $response = NimbleAPIPayments::getPayment($nimble_api, $transaction_id, true);
            } catch (Exception $e) {
               //to do
            }
        }

        if (isset($response) && isset($response['result']) && isset($response['result']['code']) && 200 == $response['result']['code']) {
            $sale = $response['data']['amount']['value'];
            $dateSale = $response['data']['date'];
            $balance = $response['data']['balance']['value'];
            $currency = $response['data']['balance']['currency'];
            if( isset($response['data']['refunds']) ){
                $refunds = $response['data']['refunds'];
            }
            $maskedpan = $response['data']['maskedPAN'];
            $refunded = array();
            $fee = 0;
            $feecurrency = ' - ';
            $feeRefund = 0;
            $feeRefundcurrency = ' - ';
            $feetotal = 0;
            $feetotalRefund = 0;
            $total = 0;
            
            if( 'SETTLED' == $response['data']['state'] && isset($response['data']['fee']) && isset($response['data']['fee']['amount']) ){
                    $fee = $response['data']['fee']['amount'];
                    $feecurrency = $response['data']['fee']['currency'];    
            }   

            for($i=0; $i<count($refunds); $i++){
                $refunded[$i]['amount']   = $refunds[$i]['refund']['amount'];
                $refunded[$i]['currency'] = $refunds[$i]['refund']['currency'];
                $refunded[$i]['date']     = $refunds[$i]['refundDate'];
                if( 'SETTLED' == $response['data']['state'] && isset($refunds[$i]['refundFee']) && isset($refunds[$i]['refundFee']['amount']) ){
                    $feeRefund = $refunds[$i]['refundFee']['amount'];
                    $feeRefundcurrency = $refunds[$i]['refundFee']['currency'];
                    $feetotalRefund += $refunds[$i]['refundFee']['amount'];
                }
            }
            $feetotal = $fee - $feetotalRefund;
            $total = $balance - $feetotal;
            
            $this->context->smarty->assign(array(
                'card'         => $maskedpan,
                'sale'         => $sale,
                'currency'     => $currency,
                'balance'      => $balance,
                'dateSale'     => $dateSale,
                'refunded'     => $refunded,
                'fee'         => $fee,
                'feecurrency' => $feecurrency,
                'feeRefund'   => $feeRefund,
                'feeRefundcurrency'   => $feeRefundcurrency,
                'feetotal'       => $feetotal,
                'total'       => $total
            ));

            $template = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'nimblepayment/views/templates/hook/order_detail.tpl');
        }
        
        die($template);
    }
}
