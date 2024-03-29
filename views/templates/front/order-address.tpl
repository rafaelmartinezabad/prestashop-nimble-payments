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
<h1 class="page-heading step-num">{l s='Addresses' mod='nimblepayment'}</h1>
<div id="opc_account" class="opc-main-block">
    <div id="opc_account-overlay" class="opc-overlay" style="display: none;"></div>
    <div class="addresses clearfix">
            <div class="row">
                    <div class="col-xs-12 col-sm-9">
                            <div class="address_delivery select form-group selector1">
                                    <label for="id_address_delivery">{if $cart->isVirtualCart()}{l s='Choose a billing address:'}{else}{l s='Choose a delivery address:'}{/if}</label>
                                    <select name="id_address_delivery" id="id_address_delivery" class="address_select form-control">
                                            {foreach from=$addresses key=k item=address}
                                                    <option value="{$address.id_address|intval}"{if $address.id_address == $cart->id_address_delivery} selected="selected"{/if}>
                                                            {$address.alias|escape:'html':'UTF-8'}
                                                    </option>
                                            {/foreach}
                                    </select><span class="waitimage"></span>
                            </div>
                            <p class="checkbox addressesAreEquals"{if $cart->isVirtualCart()} style="display:none;"{/if}>
                                    <input type="checkbox" name="same" id="addressesAreEquals" value="1"{if $cart->id_address_invoice == $cart->id_address_delivery || $addresses|@count == 1} checked="checked"{/if} />
                                    <label for="addressesAreEquals">{l s='Use the delivery address as the billing address.'}</label>
                            </p>
                    </div>
                    <div class="col-xs-12 col-sm-9">
                            <div id="address_invoice_form" class="select form-group selector1"{if $cart->id_address_invoice == $cart->id_address_delivery} style="display: none;"{/if}>
                                    {if $addresses|@count > 1}
                                            <label for="id_address_invoice" class="strong">{l s='Choose a billing address:'}</label>
                                            <select name="id_address_invoice" id="id_address_invoice" class="address_select form-control">
                                            {section loop=$addresses step=-1 name=address}
                                                    <option value="{$addresses[address].id_address|intval}"{if $addresses[address].id_address == $cart->id_address_invoice && $cart->id_address_delivery != $cart->id_address_invoice} selected="selected"{/if}>
                                                            {$addresses[address].alias|escape:'html':'UTF-8'}
                                                    </option>
                                            {/section}
                                            </select><span class="waitimage"></span>
                                    {else}
                                            <a href="{$link->getPageLink('address', true, NULL, "back={$back_order_page}&select_address=1{if $back}&mod={$back}{/if}")|escape:'html':'UTF-8'}" title="{l s='Add'}" class="button button-small btn btn-default">
                                                    <span>
                                                            {l s='Add a new address'}
                                                            <i class="icon-chevron-right right"></i>
                                                    </span>
                                            </a>
                                    {/if}
                            </div>
                    </div>
            </div> <!-- end row -->
            <div class="row">
                    <div class="col-xs-12 col-sm-6"{if $cart->isVirtualCart()} style="display:none;"{/if}>
                            <ul class="address item box" id="address_delivery">
                            </ul>
                    </div>
                    <div class="col-xs-12 col-sm-6">
                            <ul class="address alternate_item{if $cart->isVirtualCart()} full_width{/if} box" id="address_invoice">
                            </ul>
                    </div>
            </div> <!-- end row -->
            <p class="address_add submit">
                    <a href="{$link->getPageLink('address', true, NULL, "back={$back_order_page}{if $back}&mod={$back}{/if}")|escape:'html':'UTF-8'}" title="{l s='Add'}" class="button button-small btn btn-default">
                            <span>{l s='Add a new address'}<i class="icon-chevron-right right"></i></span>
                    </a>
            </p>
    </div> <!-- end addresses -->
</div> <!--  end opc_account -->
{strip}
{capture}{if $back}&mod={$back|urlencode|escape:'quotes':'UTF-8'}{/if}{/capture}
{capture name=addressUrl}{$link->getPageLink('address', true, NULL, 'back='|cat:$back_order_page|cat:$smarty.capture.default)|escape:'quotes':'UTF-8'}{/capture}
{addJsDef addressUrl=$smarty.capture.addressUrl}
{capture}{'&multi-shipping=1'|urlencode|escape:'quotes':'UTF-8'}{/capture}
{addJsDef addressMultishippingUrl=$smarty.capture.addressUrl|cat:$smarty.capture.default}
{capture name=addressUrlAdd}{$smarty.capture.addressUrl|cat:'&id_address='|escape:'quotes':'UTF-8'}{/capture}
{addJsDef addressUrlAdd=$smarty.capture.addressUrlAdd}
{addJsDef formatedAddressFieldsValuesList=$formatedAddressFieldsValuesList}
{addJsDef opc=$opc|boolval}
{capture}<h3 class="page-subheading">{l s='Your billing address' mod='nimblepayment' js=1}</h3>{/capture}
{addJsDefL name=titleInvoice}{$smarty.capture.default|@addcslashes:'\''|escape:'quotes':'UTF-8'}{/addJsDefL}
{capture}<h3 class="page-subheading">{l s='Your delivery address' mod='nimblepayment' js=1}</h3>{/capture}
{addJsDefL name=titleDelivery}{$smarty.capture.default|@addcslashes:'\''|escape:'quotes':'UTF-8'}{/addJsDefL}
{capture}<a class="button button-small btn btn-default" href="{$smarty.capture.addressUrlAdd|escape:'quotes':'UTF-8'}" title="{l s='Update' mod='nimblepayment' js=1}"><span>{l s='Update' mod='nimblepayment' js=1}<i class="icon-chevron-right right"></i></span></a>{/capture}
{addJsDefL name=liUpdate}{$smarty.capture.default|@addcslashes:'\''|escape:'quotes':'UTF-8'}{/addJsDefL}
{/strip}