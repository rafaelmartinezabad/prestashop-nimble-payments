{*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<link href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/nimble.css{$version_css}" rel="stylesheet" type="text/css" media="all">


<div id="nimble-refund-panel">
    <div class="row">
	<div class="col-lg-12">
            <div class="panel">
                {if $smarty.const._PS_VERSION_ >= 1.6}
                    <div class="panel-heading"><i class="icon-AdminNimble"></i> {l s='Nimble Payments Refunds' mod='nimblepayment'}</div>
                {else}
                    <div class="panel-heading ps15-title"><i class="icon-AdminNimble"></i> {l s='Nimble Payments Refunds' mod='nimblepayment'}</div>                    
                {/if}
                {if $new_refund_message_class != ""}
                    <div class="bootstrap">
                        <div class="module_confirmation conf confirm alert alert-{$new_refund_message_class|escape:'htmlall':'UTF-8'}">
                            <button data-dismiss="alert" class="close" type="button">×</button>
                            {$new_refund_message|escape:'htmlall':'UTF-8'}
                        </div>
                    </div>
                {/if}
                <div class="row">
                    <div id="refund-form-wrapper" class="col-xs-4">
                        {if $smarty.const._PS_VERSION_ >= 1.6}
                            <div class="form">
                        {else}
                            <div class="form ps15">
                        {/if}
                            <form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}">
                                <input type="hidden" name="id_order" value="{$params.id_order|intval}" />
                                <input type="hidden" name="refunded" id="totalQtyRefunded"value="{$refunded|escape:'htmlall':'UTF-8'}" />
                                <input type="hidden" name="order_amount" id="totalQtyPaid"value="{$order_amount|escape:'htmlall':'UTF-8'}" />
                                <fieldset class="radio-group">
                                    <div class="radio col">
                                        <input type="radio" name="refundType" value="refundTotal" checked="checked" id="refundAll"> 
                                        <label for="refundAll" class="label-text">
                                            {capture "refundable"}{l s='(_AMOUNT__CURRENCY_ sale)' mod='nimblepayment'}{/capture}
                                            <span>{l s='Total refund' mod='nimblepayment'} <strong>{$smarty.capture.refundable|replace:'_AMOUNT_':$refundable|replace:'_CURRENCY_':$order_currency|escape:'htmlall':'UTF-8'}</strong></span>
                                        </label>
                                    </div>

                                    <div class="radio col">
                                        <input type="radio" name="refundType" value="refundPartial" id="refundPartial">
                                        <label for="refundPartial" class="label-text">
                                            <span>{l s='Partial refund' mod='nimblepayment'}</span>
                                        </label>
                                    </div>
                                 </fieldset>
                                    <label for="description">
                                        <span class="label-text">{l s='Concept:' mod='nimblepayment'}</span>
                                        <input type="text" class="refundfield desc" name="description" value="{l s='Order refund with reference ' mod='nimblepayment'}{$order_reference|escape:'htmlall':'UTF-8'}"  required/>
                                    </label>

                                    <label for="reason">
                                        <select name="reason">
                                            <option value="NO_INFORMED" selected>{l s='Select the reason' mod='nimblepayment'}</option>
                                            <option value="REQUEST_BY_CUSTOMER">{l s='Customer request' mod='nimblepayment'}</option>
                                            <option value="DUPLICATE">{l s='Duplicated' mod='nimblepayment'}</option>
                                            <option value="FRAUDULENT">{l s='Fraudulent' mod='nimblepayment'}</option>
                                        </select>
                                    </label>
                                    <div class="amount hidden">
                                        <label for="amount">
                                            <span class="label-text">{l s='Amount:' mod='nimblepayment'}</span>
                                            <input type="number" min="0.06" max="{$refundable|string_format:'%.2f'|escape:'htmlall':'UTF-8'}" step="0.01" class="refundfield-amount" name="amount" value="{$refundable|string_format:'%.2f'|escape:'htmlall':'UTF-8'}" />
                                        </label>
                                    </div>
                                    <p class="btn-autorize">
                                        <button type="submit" id="btn-refund-order" class="btn btn btn-nimble link" name="submitNimbleRefund" onclick="return validateOrderRefund();">
                                                <i class="icon-undo"></i>
                                                {l s='Refund' mod='nimblepayment'}
                                        </button>
                                    </p>
                            </form>
                        </div>
                    </div>                                  
                    <div id="refund-form-list" class="col-xs-7">
                        <h3>{l s='Previous refunds' mod='nimblepayment'}</h3>
                        <div class="">
                            <table class="table" width="100%" cellspacing="10" cellpadding="10">
                                <tr>
                                   <td class="refund-nimble-title">{l s='Refund Date' mod='nimblepayment'}</td>
                                   <td class="refund-nimble-title">{l s='Refunded Amount' mod='nimblepayment'}</td>
                                   <td class="refund-nimble-title">{l s='Refund Concept' mod='nimblepayment'}</td>
                                 </tr>
                                {foreach from=$list_refunds item=list}   
                                 <tr>
                                   <td>{date("Y-m-d", strtotime($list.refundDate))|escape:'htmlall':'UTF-8'}</td>
                                   <td>{number_format($list.refund['amount'] / 100, 2, ",", ".")|escape:'htmlall':'UTF-8'} {$list.refund['currency']|escape:'htmlall':'UTF-8'}</td> 
                                   <td>{$list.refundConcept|escape:'htmlall':'UTF-8'}</td> 
                                 </tr>
                            {/foreach}
                            </table>
                        </div>
                    </div>
                </div>
            </div>
	</div>
    </div>
</div>
<script type="text/javascript">
    function validateOrderRefund(){
        return ! ("" === jQuery('#nimble-refund-panel input[name="amount"]').val());
    }
    
    function typeRefundChange(){
        if(jQuery('input[name="refundType"]:checked').val() === 'refundTotal') {
            jQuery('div.amount').addClass('hidden');
        } else {
            jQuery('div.amount').removeClass('hidden');
        }
    }

    jQuery('input[name="refundType"]').change(function() {
        typeRefundChange();
    });
</script>
