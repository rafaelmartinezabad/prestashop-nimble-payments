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
{if $smarty.const._PS_VERSION_ >= 1.6}
<div id="nimble-refund-panel"></div>
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
						<label for="amount">{l s='Refund amount' mod='nimblepayment'}:</label><input type="text" class="refundfield amount" name="amount" value="{($order_amount-$refunded)|escape:'htmlall':'UTF-8'}" /><span>{$order_currency|escape:'htmlall':'UTF-8'}</span>
						<p class="center">
							<button type="submit" class="btn btn-default submit-refund-nimble" name="submitNimbleRefund" onclick="if (!confirm('{l s='Are you sure to refund?' mod='nimblepayment'}')||!orderNimbleRefund('{l s='No es posible devolver este artículo' mod='nimblepayment'}', '{l s='La cantidad a devolver es superior a la cantidad disponible' mod='nimblepayment'}')) return false;">
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
{else}
<br />
<fieldset {if isset($ps_version) && ($ps_version < '1.5')}style="width: 400px"{/if}>
	<legend><img src="{$base_url|escape:'htmlall':'UTF-8'}modules/{$module_name|escape:'htmlall':'UTF-8'}/views/img/nimble.png" alt="" />{l s='PayPal Refund' mod='nimblepayment'}</legend>
	<p><b>{l s='Information:' mod='nimblepayment'}</b> {l s='Payment accepted' mod='nimblepayment'}</p>
	<p><b>{l s='Information:' mod='nimblepayment'}</b> {l s='When you refund a product, a partial refund is made unless you select "Generate a voucher".' mod='nimblepayment'}</p>
	<table class="table" width="100%" cellspacing="0" cellpadding="0">
			  <tr>
			    <th>{l s='Refund date' mod='nimblepayment'}</th>
			    <th>{l s='Refund Amount' mod='nimblepayment'}</th> 
			    <th>{l s='Refund state' mod='nimblepayment'}</th>
			  </tr>
			{foreach from=$list_refunds item=list}
			  <tr>
			    <td>{Tools::displayDate($list.refundDate, $smarty.const.null,true)|escape:'htmlall':'UTF-8'}</td>
			    <td>{$list.refund / 100|escape:'htmlall':'UTF-8'}</td> 
			    <td>{$list.refundState|escape:'htmlall':'UTF-8'}</td>
			  </tr>
			{/foreach}
			</table>
	<form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}">
		<input type="hidden" name="id_order" value="{$params.id_order|intval}" />
		<p class="center">
			<input type="submit" class="button" name="submitNimbleRefund" value="{l s='Refund total transaction' mod='nimblepayment'}" onclick="if (!confirm('{l s='Are you sure?' mod='nimblepayment'}'))return false;" />
		</p>
	</form>
</fieldset>

{/if}
