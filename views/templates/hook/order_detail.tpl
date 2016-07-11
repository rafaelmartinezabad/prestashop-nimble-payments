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
<td id="nimble-payment-details-td" colspan="6">
    <div id="nimble-payment-details">
        <h3 class="panel-heading"><i class="icon-AdminNimble"></i> {l s='Detail of movements' mod='nimblepayment'}</h3>
        <table>
            <tr>
               <th scope="col">{l s='' mod='nimblepayment'}</th>
               <th scope="col">{l s='' mod='nimblepayment'}</th>
               <th scope="col">{l s='Amount' mod='nimblepayment'}</th>
               <th scope="col" colspan="2">{l s='Discounts' mod='nimblepayment'}</th>
             </tr>
             <tr>
                 <td class="nimble-date">{date("Y-m-d", strtotime($dateSale))|escape:'htmlall':'UTF-8'}</td>
                 <td class="nimble-detail">{l s='Sale' mod='nimblepayment'}</td>
                 <td>{displayPrice price=($sale / 100) currency=$currency}</td>
                 <td class="nimble-empty"></td>
                 {if $fee != 0}
                     <td>{number_format($fee / 100, 2, ",", ".")|escape:'htmlall':'UTF-8'} {$currency|escape:'htmlall':'UTF-8'}</td>
                 {else}
                      <td> - </td>
                 {/if}    
             </tr>
            {foreach from=$refunded item=list}
             <tr>
                <td class="nimble-date">{date("Y-m-d", strtotime($list['date']))|escape:'htmlall':'UTF-8'}</td>
                <td class="nimble-detail">{l s='Refund' mod='nimblepayment'}</td>
                <td>- {displayPrice price=($list['amount'] / 100) currency=$list['currency']}</td>
                <td class="nimble-empty"></td>
                {if $feeRefund != 0}
                    <td>- {displayPrice price=($feeRefund / 100) currency=$list['currency']}</td>
                {else}
                    <td> - </td>
                 {/if} 
             </tr>
             {/foreach}
             <tr>
                 <td></td>
                 <td></td>
                 <td class="partial">{displayPrice price=($balance / 100) currency=$currency}</td>
                 <td class="nimble-empty"></td>
                 {if $feetotal != 0}
                    <td class="partial">{displayPrice price=($feetotal / 100) currency=$currency}</td>
                 {else}
                    <td class="partial"> - </td>
                 {/if}   
             </tr>
             <tr>
                <td></td>
                <td></td>
                <td class="total" colspan="2">{l s='Final Balance' mod='nimblepayment'}</td>
                <td class="total">{displayPrice price=($total / 100) currency=$currency}</td>
             </tr>
        </table>
    </div>
</td>

                 
                      
                     

