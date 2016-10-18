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

{assign var="orderOpcUrl" value=$orderOpcUrl}
{assign var="back_order_page" value=$link->getModuleLink('nimblepayment', 'fastercheckout', $params, $ssl)|urlencode|escape:'htmlall':'UTF-8'}

{if $nimble_credentials}
		{if $faster_checkout_enabled}
			{if $productNumber}
				<div class="col-md-6">
					<div class="nimble-block" id="nimble-shopping-cart">
						{if $is_logged AND !$is_guest}
							{include file="./order-address.tpl"}
						{else}
							<!-- Create account / Guest account / Login block -->
							{include file="./order-opc-new-account.tpl"}
							<!-- END Create account / Guest account / Login block -->
						{/if}
					</div>	
					<div class="nimble-block" id="nimble-order-carrier">
						<!-- Carrier -->
						{include file="./order-carrier.tpl"}
						<!-- END Carrier -->
					</div>
				</div>
				<div class="col-md-6">	
					<div class="nimble-block" id="nimble-order-address">
						{include file="./shopping-cart.tpl"}
					</div>					
					<div class="nimble-block" id="nimble-order-payment">
						<!-- Payment -->
						{include file="./order-payment.tpl"}
					</div>
				</div>
			{else}
				{capture name=path}{l s='Your shopping cart'}{/capture}
				<h2 class="page-heading">{l s='Your shopping cart'}</h2>
				{include file="$tpl_dir./errors.tpl"}
				<p class="alert alert-warning">{l s='Your shopping cart is empty.'}</p>
			{/if}
		{else}
			<h2 class="page-heading">{l s='Faster Checkout is disable' mod='nimblepayment'}</h2>
			{include file="$tpl_dir./errors.tpl"}
			<p class="alert alert-warning">{l s='Faster Checkout is disable' mod='nimblepayment'}</p>
		{/if}
{else}
	<h2 class="page-heading">{l s='The gateway Nimble Payments is not active'}</h2>
	{include file="$tpl_dir./errors.tpl"}
	<p class="alert alert-warning">{l s='The gateway Nimble Payments is not active'}</p>
{/if}
{strip}
{addJsDef orderOpcUrl=$orderOpcUrl}
{addJsDef ajaxRandQueryParam=$ajaxRandQueryParam}
{addJsDef authenticationUrl=$link->getPageLink("authentication", true, NULL, "back={$back_order_page}")|escape:'quotes':'UTF-8'}
{addJsDef historyUrl=$link->getPageLink("history", true)|escape:'quotes':'UTF-8'}
{addJsDef guestTrackingUrl=$link->getPageLink("guest-tracking", true)|escape:'quotes':'UTF-8'}
{addJsDef addressUrl=$link->getPageLink("address", true, NULL, "back={$back_order_page}")|escape:'quotes':'UTF-8'}
{addJsDef orderProcess='order-opc'}
{addJsDef guestCheckoutEnabled=$PS_GUEST_CHECKOUT_ENABLED|intval}
{addJsDef displayPrice=$priceDisplay}
{addJsDef taxEnabled=$use_taxes}
{addJsDef conditionEnabled=$conditions|intval}
{addJsDef vat_management=$vat_management|intval}
{addJsDef errorCarrier=$errorCarrier|@addcslashes:'\''}
{addJsDef errorTOS=$errorTOS|@addcslashes:'\''}
{addJsDef checkedCarrier=$checked|intval}
{addJsDef addresses=array()}
{addJsDef isVirtualCart=$isVirtualCart|intval}
{addJsDef isPaymentStep=$isPaymentStep|intval}
{addJsDefL name=txtWithTax}{l s='(tax incl.)' js=1}{/addJsDefL}
{addJsDefL name=txtWithoutTax}{l s='(tax excl.)' js=1}{/addJsDefL}
{addJsDefL name=txtHasBeenSelected}{l s='has been selected' js=1}{/addJsDefL}
{addJsDefL name=txtNoCarrierIsSelected}{l s='No carrier has been selected' js=1}{/addJsDefL}
{addJsDefL name=txtNoCarrierIsNeeded}{l s='No carrier is needed for this order' js=1}{/addJsDefL}
{addJsDefL name=txtConditionsIsNotNeeded}{l s='You do not need to accept the Terms of Service for this order.' js=1}{/addJsDefL}
{addJsDefL name=txtTOSIsAccepted}{l s='The service terms have been accepted' js=1}{/addJsDefL}
{addJsDefL name=txtTOSIsNotAccepted}{l s='The service terms have not been accepted' js=1}{/addJsDefL}
{addJsDefL name=txtThereis}{l s='There is' js=1}{/addJsDefL}
{addJsDefL name=txtErrors}{l s='Error(s)' js=1}{/addJsDefL}
{addJsDefL name=txtDeliveryAddress}{l s='Delivery address' js=1}{/addJsDefL}
{addJsDefL name=txtInvoiceAddress}{l s='Invoice address' js=1}{/addJsDefL}
{addJsDefL name=txtModifyMyAddress}{l s='Modify my address' js=1}{/addJsDefL}
{addJsDefL name=txtInstantCheckout}{l s='Instant checkout' mod='nimblepayment' js=1}{/addJsDefL}
{addJsDefL name=txtSelectAnAddressFirst}{l s='Please start by selecting an address.' js=1}{/addJsDefL}
{addJsDefL name=txtFree}{l s='Free' js=1}{/addJsDefL}

{capture}{if $back}&mod={$back|urlencode}{/if}{/capture}
{capture name=addressUrl}{$link->getPageLink('address', true, NULL, 'back='|cat:$back_order_page|cat:'?step=1'|cat:$smarty.capture.default)|escape:'quotes':'UTF-8'}{/capture}
{addJsDef addressUrl=$smarty.capture.addressUrl}
{capture}{'&multi-shipping=1'|urlencode}{/capture}
{addJsDef addressMultishippingUrl=$smarty.capture.addressUrl|cat:$smarty.capture.default}
{capture name=addressUrlAdd}{$smarty.capture.addressUrl|cat:'&id_address='}{/capture}
{addJsDef addressUrlAdd=$smarty.capture.addressUrlAdd}
{addJsDef opc=$opc|boolval}
{capture}<h3 class="page-subheading">{l s='Your billing address' mod='nimblepayment' js=1}</h3>{/capture}
{addJsDefL name=titleInvoice}{$smarty.capture.default|@addcslashes:'\''}{/addJsDefL}
{capture}<h3 class="page-subheading">{l s='Your delivery address' mod='nimblepayment' js=1}</h3>{/capture}
{addJsDefL name=titleDelivery}{$smarty.capture.default|@addcslashes:'\''}{/addJsDefL}
{capture}<a class="button button-small btn btn-default" href="{$smarty.capture.addressUrlAdd}" title="{l s='Update' mod='nimblepayment' js=1}"><span>{l s='Update' mod='nimblepayment' js=1}<i class="icon-chevron-right right"></i></span></a>{/capture}
{addJsDefL name=liUpdate}{$smarty.capture.default|@addcslashes:'\''}{/addJsDefL}
{/strip}
{strip}

<script type="text/javascript">
    $(document).ready(function () {
        overrideButtonRemoveCartFasterCheckout();
    });
</script>