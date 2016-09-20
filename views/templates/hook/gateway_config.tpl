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
    <h2 class="subtitle">{$subtitle|escape:'htmlall':'UTF-8'}</h2>
</div >
{if !$gateway_enabled}
    <div class="nimbleStepBox step1">
        <h3>{l s='Step 1: sign up in Nimble Payments' mod='nimblepayment'}</h3>
        <img class="logo" alt="logo-alta" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icono_registrate_redondo.png"/>
        <div class="contentStep">
            <p class="subtitle">{l s='Need an Nimble Payments account? It is completely free and 100% online.' mod='nimblepayment'}</p>
            <p>{l s='Sign up now and start selling online.' mod='nimblepayment'}
                <img class="link" alt="logo-alta-link" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icono_registrate_cuadrado.png"/>
                <a class="btn" href="https://www.nimblepayments.com/private/registration?utm_source=Prestashop_BackOffice&utm_medium=Referral%20Partners&utm_campaign=Creacion-Cuenta&partner=prestashop" target="_blank" class="link">{l s='Sign up here' mod='nimblepayment'}</a>
            </p>
        </div>
    </div>
    <div class="nimbleStepBox step2">
        <h3>{l s='Step 2: set up your module' mod='nimblepayment'}</h3>
        <img class="logo" alt="logo-config" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icono_configura.png"/>
        <div class="contentStep">
            <p class="subtitle">{l s='To be able to accept payments you only need to use the credentials you get in Nimble Payments.' mod='nimblepayment'}</p>
            <p>{l s='If you don\'t have them available, check there here.' mod='nimblepayment'}
                <img class="link" alt="logo-gateway-link" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icono_consultalos.png"/>
                <a class="btn" onclick="window.open('{$url_nimble|escape:'htmlall':'UTF-8'}', '', 'width=800, height=578')">{l s='Check them here' mod='nimblepayment'}</a>
            </p>
            {if $error_message}
            <div class="module_error alert alert-danger">
                    <button data-dismiss="alert" class="close" type="button">×</button>{l s='Payment gateway data not valid to accept payments.' mod='nimblepayment'}
            </div>
            {/if}
            <form method="post" action="{$post_url|escape:'htmlall':'UTF-8'}">
                <input type="hidden" value="1" name="saveCredentials">
                <div class="form-group">
                    <label>{l s='API client id' mod='nimblepayment'}</label>
                    <div>
                        <input type="text" class="" value="{$clientId|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_ID" name="NIMBLEPAYMENT_CLIENT_ID">
                    </div>
                </div>
                <div class="form-group">
                    <label>{l s='Client secret' mod='nimblepayment'}</label>
                    <div>
                        <input type="text" class="" value="{$clientSecret|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_SECRET" name="NIMBLEPAYMENT_CLIENT_SECRET">
                    </div>
                </div>
                <div class="form-footer">
                    <button name="saveCredentials" id="module_form_submit_btn" value="1" type="submit">{l s='Save'  mod='nimblepayment'}</button>
                </div>
            </form>
        </div>
    </div>
{else}
    <div class="nimbleStepBox">
        <h3>{l s='Nimble Payments gateway data' mod='nimblepayment'}</h3>
        <div class="contentStep">
            <form method="post" action="{$post_url|escape:'htmlall':'UTF-8'}">
                <input type="hidden" value="1" name="saveCredentials">
                <div class="form-group">
                    <label>{l s='API client id' mod='nimblepayment'}</label>
                    <div>
                        <input type="text" class="" value="{$clientId|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_ID" name="NIMBLEPAYMENT_CLIENT_ID">
                    </div>
                </div>
                <div class="form-group">
                    <label>{l s='Client secret' mod='nimblepayment'}</label>
                    <div>
                        <input type="text" class="" value="{$clientSecret|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_SECRET" name="NIMBLEPAYMENT_CLIENT_SECRET">
                    </div>
                </div>
                <div class="form-footer">
                    <button name="saveCredentials" id="module_form_submit_btn" value="1" type="submit">{l s='Save' mod='nimblepayment'}</button>
                </div>
            </form>
        </div>
    </div>
	<div class="nimbleStepBox">
		<h3>{l s='Nimble Payments faster checkout' mod='nimblepayment'}</h3>
		<div class="contentStep">
			<input type="hidden" value="1" name="saveFaster">
			<form method="post" action="{$post_url|escape:'htmlall':'UTF-8'}">
				<div class="form-group">
					<label>{l s='Enabled/Disable' mod='nimblepayment'}</label>
				</div>
				<div class="col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="fasterCheckout" id="fasterCheckout_on" value="1" {if $faster_checkout} checked="checked" {/if}>
						<label for="fasterCheckout_on" class="radioCheck">Sí</label>
						<input type="radio" name="fasterCheckout" id="fasterCheckout_off" value="0" {if !$faster_checkout} checked="checked" {/if}>
						<label for="fasterCheckout_off" class="radioCheck">No</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="form-footer">
					<button name="saveFaster" id="module_form_submit_btn" value="1" type="submit">{l s='Save' mod='nimblepayment'}</button>
				</div>
			</form>
		</div>
	</div>
    {if !$authorized}
        <div class="nimbleAuthorize">
            <div class="title">
                <p>{l s='From Prestashop you will be able to manage all your sales,' mod='nimblepayment'}<br/>
                {l s='make refunds, etc' mod='nimblepayment'}</p>
            </div>
            <div class="separator"></div>
            <div class="box">
                <p>{l s='To access to all the Nimble Payments features from Prestashop, you have to be identified in Nimble Payments and allow Prestashop to access to this information.' mod='nimblepayment'}</p>
            </div>
            <div class="box">
                <a id="authorize_btn" href="{$Oauth3Url|escape:'htmlall':'UTF-8'}" class="link">{l s='Authorize Prestashop' mod='nimblepayment'}</a>
            </div>
        </div>
    {else}
        <div class="nimbleAuthorize">
            <div class="box">
                <img class="logo" alt="logo-authorized" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icon-linked.png"/>
                <p>{l s='Ecommerce linked to Nimble Payments' mod='nimblepayment'}</p>
            </div>
            <div class="box">
                <form method="post" action="{$post_url|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" value="1" name="removeOauth2">
                        <button name="removeOauth2" id="unauthorize_btn" value="1" type="submit">{l s='Disassociate' mod='nimblepayment'}</button>
                </form>
            </div>
        </div>
    {/if}
{/if}