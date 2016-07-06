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

<link href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/gateway_config.css" rel="stylesheet" type="text/css" media="all">

<div class="row nimbleHeader">
    <h1 class="title">Nimble Payments: {l s='Wellcome to change' mod='nimblepayment'}</h1>
    <h2 class="subtitle">{$subtitle|escape:'htmlall':'UTF-8'}</h2>
</div >
{if !$gateway_enabled}
<div class="button-registration">
    <p class="btn">
         <a href="https://www.nimblepayments.com/private/registration?utm_source=Prestashop_BackOffice&utm_medium=Referral%20Partners&utm_campaign=Creacion-Cuenta&partner=prestashop" target="_blank" class="link">{l s='Try now!' mod='nimblepayment'}</a>
    </p>                   
    <p class="btn">
        <a class="link" onclick="window.open('{$url_nimble|escape:'htmlall':'UTF-8'}', '', 'width=800, height=578')">{l s='Already registered.' mod='nimblepayment'}</a>
    </p>
</div>
{/if}