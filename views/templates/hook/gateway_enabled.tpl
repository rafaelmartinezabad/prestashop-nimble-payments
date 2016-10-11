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

{if $success_message}
<div class="bootstrap">
	<div class="module_confirmation conf confirm alert alert-success">
		<button data-dismiss="alert" class="close" type="button">×</button>
		{l s='Settings saved successfully!' mod='nimblepayment'}
	</div>
</div>
{/if}

<div class="nimbleHeader">
    <h1 class="title"><strong>Nimble Payments:</strong> {l s='WELCOME TO THE CHANGE' mod='nimblepayment'}</h1>
    <p class="subtitle">{l s='Your payment module is ready to work!' mod='nimblepayment'}</p>
</div >
<div class="nimbleStepBox step2">
	<div class="contentStep">
		{if $error_message}
			<div class="module_error alert alert-danger">
				<button data-dismiss="alert" class="close" type="button">×</button>{l s='Payment gateway data not valid to accept payments.' mod='nimblepayment'}
			</div>
		{/if}
		<form id="nimble-step-2-form-enable" class="form" style="display: none;" method="post" action="{$post_url|escape:'htmlall':'UTF-8'}">
            <div id="nimble-content-form">
                <h3>{l s='Your nimble payments credentials' mod='nimblepayment'}</h3>
                <input type="hidden" value="1" name="saveCredentials">
                <div class="form-group form-group-enabled">
                    <table>
                        <tr>
                            <td class="label-nimble-table">
                                <label>{l s='API client id' mod='nimblepayment'}</label>
                            </td>
                            <td class="input-nimble-table">
                                <input type="text" class="" value="{$clientId|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_ID" name="NIMBLEPAYMENT_CLIENT_ID">
                            </td> 
                        </tr>
                        <tr>
                            <td class="label-nimble-table">
                                <label>{l s='Client secret' mod='nimblepayment'}</label>
                            </td>
                            <td class="input-nimble-table">
                                <input type="text" class="" value="{$clientSecret|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_SECRET" name="NIMBLEPAYMENT_CLIENT_SECRET">
                            </td>
                        </tr>
                    </table>
    			</div>
                <input type="hidden" value="1" name="saveFaster">
            </div>
            <div id="nimble-content-form-2">
                <h3>{l s='Payment options' mod='nimblepayment'}</h3>
                <div id="faster-div" class="form-group">
                    <div>{l s='Quick pay option' mod='nimblepayment'}:</div>
                    <div class="radio col">
                        <input name="fasterCheckout" value="1" {if $faster_checkout} checked="checked" {/if} id="fasterCheckout_on" type="radio">
                        <label for="fasterCheckout_on" class="label-text">
                            <span>{l s='Yes'}</span>
                        </label>
                        <input name="fasterCheckout" value="0" {if !$faster_checkout} checked="checked" {/if} id="fasterCheckout_off" type="radio">
                        <label for="fasterCheckout_off" class="label-text">
                            <span>{l s='No'}</span>
                        </label>
                    </div>
                    <div><span id="question-icon"></span> {l s='This option allows to buy with only one click' mod='nimblepayment'}</div>
                </div>
            </div>
			<div class="form-footer-2">
                <button name="cancelCredentials" id="module_form_cancel_btn" value="1" type="cancel">{l s='Cancel'}</button>
				<button name="saveCredentials" id="module_form_submit_btn" value="1" type="submit">{l s='Save'}</button>
			</div>
		</form>
        <div id="nimble-step-2-div-enable" class="form">
            <div id="nimble-content-form">
                <h3>{l s='Your nimble payments credentials' mod='nimblepayment'}</h3>
                <div class="form-group form-group-enabled">
                    <table>
                        <tr>
                            <td class="label-nimble-table">
                                <label>{l s='API client id' mod='nimblepayment'}</label>
                            </td>
                            <td class="input-nimble-table">
                                <strong>{$clientId|escape:'htmlall':'UTF-8'}</strong>
                            </td> 
                        </tr>
                        <tr>
                            <td class="label-nimble-table">
                                <label>{l s='Client secret' mod='nimblepayment'}</label>
                            </td>
                            <td class="input-nimble-table">
                                <strong>{$clientSecret|escape:'htmlall':'UTF-8'}</strong>
                            </td>
                        </tr>
                    </table>
                </div>
                <input type="hidden" value="1" name="saveFaster">
            </div>
            <div id="nimble-content-form-2">
                <h3>{l s='Payment options' mod='nimblepayment'}</h3>
                <div id="faster-div" class="form-group">
                    <div>{l s='Quick pay option' mod='nimblepayment'}:</div>
                    <div class="radio col fastercheckout-enable-page">
                        {if $faster_checkout}
                            <span>{l s='Yes'}</span>
                        {else}
                            <span>{l s='No'}</span>
                        {/if}
                    </div>
                    <div><span id="question-icon"></span> {l s='This option allows to buy with only one click' mod='nimblepayment'}</div>
                </div>
            </div>
            <div class="form-footer-2">
                <img class="link" alt="logo-alta-link" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/icono_registrate_cuadrado.png">
                <a id="modifyGateway-js" class="btn" href="#" target="_blank">{l s='Update your gateway credetials' mod='nimblepayment'}</a>
            </div>
        </div>
	</div>
</div>

{if $gateway_enabled}
    {if !$authorized}
        <div class="nimbleAuthorize">
            <div class="title">
                <p>{l s='You can do everything in Prestashop site' mod='nimblepayment'}:<br/>
                {l s='manage your purchases, check your account transactions, request a refund...' mod='nimblepayment'}</p>
            </div>
            <div class="separator"></div>
            <div class="box">
                <p>{l s='log in and allow us to access your information.' mod='nimblepayment'}</p>
            </div>
            <div class="box">
                <a id="authorize_btn" href="{$Oauth3Url|escape:'htmlall':'UTF-8'}" class="link">{l s='Allow Prestashop' mod='nimblepayment'}</a>
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