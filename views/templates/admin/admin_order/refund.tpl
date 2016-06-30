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
<div id="nimble-refund-panel">
    <div class="row">
	<div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading"><i class="icon-AdminNimble"></i> {l s='Nimble Refund' mod='nimblepayment'}</div>
			{if $error != ""}
				{$error|escape:'htmlall':'UTF-8'}
			{else}
				<table class="table" width="100%" cellspacing="0" cellpadding="0">
				  <tr>
				    <th>{l s='Refund Date' mod='nimblepayment'}</th>
				    <th>{l s='Refund Amount' mod='nimblepayment'}</th> 
				    <th>{l s='Refund Concept' mod='nimblepayment'}</th> 
				    <th>{l s='Refund State' mod='nimblepayment'}</th>
				  </tr>
				{foreach from=$list_refunds item=list}
				  <tr>
				    <td>{date("Y-m-d", strtotime($list.refundDate))|escape:'htmlall':'UTF-8'}</td>
				    <td>{number_format($list.refund['amount'] / 100, 2, ",", ".")|escape:'htmlall':'UTF-8'} {$list.refund['currency']|escape:'htmlall':'UTF-8'}</td> 
				    <td>{$list.refundConcept|escape:'htmlall':'UTF-8'}</td> 
				    <td>{$list.refundState|escape:'htmlall':'UTF-8'}</td>
				  </tr>
				{/foreach}
				</table>
				<div id="refund-form-wrapper">
				{if $still_refundable}
					<form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}">
						<input type="hidden" name="id_order" value="{$params.id_order|intval}" />
						<input type="hidden" name="refunded" id="totalQtyRefunded"value="{$refunded|escape:'htmlall':'UTF-8'}" />
						<input type="hidden" name="order_amount" id="totalQtyPaid"value="{$order_amount|escape:'htmlall':'UTF-8'}" />
						<label for="description">{l s='Refund concept' mod='nimblepayment'}:</label><input type="text" class="refundfield desc" name="description" value="{l s='Refund for order with reference ' mod='nimblepayment'}{$description|escape:'htmlall':'UTF-8'}" />
						<label for="amount" >{l s='Refund amount' mod='nimblepayment'}:</label><input type="number" min="0.06" max="{($order_amount-$refunded)|escape:'htmlall':'UTF-8'}" step="0.01" class="refundfield amount" name="amount" value="{($order_amount-$refunded)|escape:'htmlall':'UTF-8'}" /><span>{$order_currency|escape:'htmlall':'UTF-8'}</span><br/>
                                                <label for="stateRefund" ><input type="checkbox" name="stateRefund" value="refund">Cambiar estado a reembolsado<br></label>

						<p class="center">
							<button type="submit" class="btn btn-default submit-refund-nimble" name="submitNimbleRefund" onclick="return validateOrderRefund();">
								<i class="icon-undo"></i>
								{l s='Refund transaction' mod='nimblepayment'}
							</button>
						</p>
					</form>
				{else}
					<p><b>{l s='Info' mod='nimblepayment'}:</b> {l s='No further refunds are available' mod='nimblepayment'}</p>
				{/if}
				</div>
			{/if}
		</div>
	</div>
    </div>
</div>
<script type="text/javascript">
    function validateOrderRefund(){
        return ! ("" === jQuery('#nimble-refund-panel input[name="amount"]').val());
    }
</script>
