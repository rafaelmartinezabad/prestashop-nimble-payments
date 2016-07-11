{*
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
*  @author    Devtopia Coop <hello@devtopia.coop>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<link href="/../../modules/nimblepayment/views/css/nimble.css" rel="stylesheet" type="text/css" media="all">

<div id="refund-form-list nimble-payment-details" class="col-xs-7">
        <table class="table" width="100%" cellspacing="10" cellpadding="10">
        <caption class="panel-heading"><i class="icon-AdminNimble"></i> {l s='Nimble Payments Details' mod='nimblepayment'}</caption>
            <thead>
                <tr>
                   <th class="refund-nimble-title" scope="col">{l s='' mod='nimblepayment'}</th>
                   <th class="refund-nimble-title" scope="col">{l s='' mod='nimblepayment'}</th>
                   <th class="refund-nimble-title" scope="col">{l s='Importes' mod='nimblepayment'}</th>
                 </tr>
            </thead>
            <tbody>
                 <tr>
                     <td>{date("Y-m-d", strtotime($dateSale))|escape:'htmlall':'UTF-8'}</td>
                     <td>Venta</td>
                     <td>{number_format($sale / 100, 2, ",", ".")|escape:'htmlall':'UTF-8'} {$currency|escape:'htmlall':'UTF-8'}</td>
                 </tr>
                {foreach from=$refunded item=list}
                 <tr>
                    <td>{date("Y-m-d", strtotime($list['date']))|escape:'htmlall':'UTF-8'}</td>
                    <td>Devolucion</td>
                    <td>{number_format($list['amount'] / 100, 2, ",", ".")|escape:'htmlall':'UTF-8'} {$list['currency']|escape:'htmlall':'UTF-8'}</td>
                 </tr>
                 {/foreach}
                 <tr class="balance-partial">
                     <td></td>
                     <td></td>
                     <td>22.01euro</td>
                 </tr>
                 <tr class="balance-total">
                    <td></td>
                    <td>Saldo Final</td>
                    <td>{number_format($balance / 100, 2, ",", ".")|escape:'htmlall':'UTF-8'} {$currency|escape:'htmlall':'UTF-8'}</td>
                 </tr>
             </tbody>    
        </table>
</div>

                 
                      
                     

