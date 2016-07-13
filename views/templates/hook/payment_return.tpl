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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if $status == 'ok'}
    <p class="alert alert-success">{l s='Your order on %s is completed.' sprintf=$shop_name mod='nimblepayment'}</p>
    {if isset($order)}
    <div class="info-order box">
            <p>{l s='Total price:' mod='nimblepayment'}
                <strong class="dark"> {$price|escape:'htmlall':'UTF-8'} </strong>
            </p>
            <p>{l s='Your order ID is:' mod='nimblepayment'} 
                    <strong class="dark">
                    {if $smarty.const._PS_VERSION_ >= 1.5}
                            {if isset($order->reference)}
                                    {$order->reference|escape:'htmlall':'UTF-8'}
                            {else}
                                    {$order->id|intval}
                            {/if}
                    {else}
                            {$order->id|intval}
                    {/if}
                    </strong>
            </p>
            {if $carrier->id}<p><strong class="dark">{l s='Carrier' mod='nimblepayment'}</strong> {if $carrier->name == "0"}{$shop_name|escape:'html':'UTF-8'}{else}{$carrier->name|escape:'html':'UTF-8'}{/if}</p>{/if}
            <p><strong class="dark">{l s='Payment method' mod='nimblepayment'}</strong> <span class="color-myaccount">{l s='Card payment' mod='nimblepayment'}</span></p>
            {if $invoice AND $invoiceAllowed}
            <p>
                    <i class="icon-file-text"></i>
                    <a target="_blank" href="{$link->getPageLink('pdf-invoice', true)|escape:'htmlall':'UTF-8'}?id_order={$order->id|intval}{if $is_guest}&amp;secure_key={$order->secure_key|escape:'html':'UTF-8'}{/if}">{l s='Download your invoice as a PDF file.' mod='nimblepayment'}</a>
            </p>
            {/if}
    </div>
    {/if}
{/if}
