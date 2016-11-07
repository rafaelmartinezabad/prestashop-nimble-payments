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

<link href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/gateway_config.css" rel="stylesheet" type="text/css" media="all">

<div class="nimbleHeader">
    <h1 class="title"><strong>Nimble Payments:</strong> {l s='WELCOME TO THE CHANGE' mod='nimblepayment'}</h1>
    <p><strong>{l s='Nimble Payments is a secure online gateway developed by BBVA that allows your payments and transactions be done in a reliable way.' mod='nimblepayment'}</strong></p>
    <ul class="nimble-ul">
        <li><green>{l s='Payment methods' mod='nimblepayment'}:</green> {l s='credit/debit card, pre and virtual payment.' mod='nimblepayment'} <img class="logo" alt="logo-alta" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/visa.jpg"/> <img class="logo" alt="logo-alta" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/mastercard.jpg"/></li>
        <li><green>{l s='Responsive design' mod='nimblepayment'}:</green> {l s='payment available for all devices: mobile, web and tablet.' mod='nimblepayment'}</li>
        <li><green>{l s='Personal Control Pannel' mod='nimblepayment'}:</green> {l s='check and manage all your transaction.' mod='nimblepayment'}</li>
        <li><green>{l s='Account without fees' mod='nimblepayment'}:</green> {l s='you will get your money here.' mod='nimblepayment'}</li>
    </ul>
</div >
<div class="nimbleStepBox step1">
	<h3>{l s='Step 1: Register in Nimble Payments' mod='nimblepayment'}</h3>
	<img class="logo" alt="logo-alta" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icono_registrate_redondo.png"/>
	<div class="contentStep">
		<p class="subtitle">{l s='You need to be registered to start working with the payment gateway.' mod='nimblepayment'}</p>
		<p>{l s='Only one email and password are needed to start testing it.' mod='nimblepayment'}
			<img class="link" alt="logo-alta-link" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icono_registrate_cuadrado.png"/>
			<a class="btn" href="https://www.nimblepayments.com/private/registration?utm_source=Prestashop_BackOffice&utm_medium=Referral%20Partners&utm_campaign=Creacion-Cuenta&partner=prestashop" target="_blank" class="link">{l s='Register here' mod='nimblepayment'}</a>
		</p>
	</div>
</div>
<div class="nimbleStepBox step2">
	<h3>{l s='Step 2: Configure your plugin' mod='nimblepayment'}</h3>
	<img class="logo" alt="logo-config" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icono_configura.png"/>
	<div class="contentStep">
		<p class="subtitle">{l s='Your Nimble Payment credentials are needed.' mod='nimblepayment'}</p>
		<p>{l s='Forgot your credentials?' mod='nimblepayment'}
			<img class="link" alt="logo-gateway-link" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icono_consultalos.png"/>
			<a class="btn" onclick="window.open('{$url_nimble|escape:'htmlall':'UTF-8'}', '', 'width=800, height=578')">{l s='Questions' mod='nimblepayment'}</a>
		</p>
		{if $error_message}
			<div class="module_error alert alert-danger">
				<button data-dismiss="alert" class="close" type="button">×</button>{l s='Payment gateway data not valid to accept payments.' mod='nimblepayment'}
			</div>
		{/if}
		<form id="nimble-step-2-form" class="form" method="post" action="{$post_url|escape:'htmlall':'UTF-8'}">
			<input type="hidden" value="1" name="saveCredentials">
			<div class="form-group">
				<label>{l s='Client id' mod='nimblepayment'}</label>
				<div>
					<input type="text" class="" value="{$clientId|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_ID" name="NIMBLEPAYMENT_CLIENT_ID">
				</div>
			</div>
			<div class="form-group">
				<label>{l s='Client secret' mod='nimblepayment'}</label>
				<div>
					<input type="password" class="" value="{$clientSecret|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_SECRET" name="NIMBLEPAYMENT_CLIENT_SECRET">
				</div>
			</div>
            <div id="faster-div" class="form-group">
                <div>{l s='Quick pay option' mod='nimblepayment'}:</div>
                <div class="radio col">
                	{if $canFasterCheckout}
                    <input name="fasterCheckout" value="1" {if $faster_checkout} checked="checked" {/if} id="fasterCheckout_on" type="radio">
                    <label for="fasterCheckout_on" class="label-text">
                        <span>{l s='Yes'}</span>
                    </label>
                    <input name="fasterCheckout" value="0" {if !$faster_checkout} checked="checked" {/if} id="fasterCheckout_off" type="radio">
                    <label for="fasterCheckout_off" class="label-text">
                        <span>{l s='No'}</span>
                    </label>
                    {else}
                        <span style="padding-left: 10px; font-style: italic">{l s='Disponible para versiones 1.6.1 o superiores' mod='nimblepayment'}</span>
                    {/if}
                </div>
                <div><span id="question-icon"></span> {l s='This option allows to buy with only one click' mod='nimblepayment'}</div>
            </div>
            <input type="hidden" value="1" name="saveFaster">

			<div class="form-footer">
				<button name="saveCredentials" id="module_form_submit_btn" value="1" type="submit">{l s='Save'}</button>
			</div>
		</form>
	</div>
</div>