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
{if $nimble_credentials}
	{if $isLogged}
		{if $faster_checkout_enabled}
			{if $productNumber}
				<div class="nimble-left">
					<div class="nimble-block" id="nimble-shopping-cart">
						{include file="./shopping-cart.tpl"}
					</div>	
					<div class="nimble-block" id="nimble-order-carrier">
						<!-- Carrier -->
						{include file="./order-carrier.tpl"}
						<!-- END Carrier -->
					</div>
				</div>
				<div class="nimble-right">	
					<div class="nimble-block" id="nimble-order-address">
						{include file="./order-address.tpl"}
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
		<h2 class="page-heading">{l s='User is not logged'}</h2>
		{include file="$tpl_dir./errors.tpl"}
		<p class="alert alert-warning">{l s='User is not logged '}</p>
	{/if}
{else}
	<h2 class="page-heading">{l s='The gateway Nimble Payments is not active'}</h2>
	{include file="$tpl_dir./errors.tpl"}
	<p class="alert alert-warning">{l s='The gateway Nimble Payments is not active'}</p>
{/if}
{addJsDef orderOpcUrl=$link->getModuleLink('nimblepayment', 'fastercheckout', $params, $ssl)|escape:'htmlall':'UTF-8'}
{addJsDef orderProcess='order-opc'}