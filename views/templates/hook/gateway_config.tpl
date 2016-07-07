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

<div class="nimbleHeader">
    <h1 class="title"><strong>Nimble Payments:</strong> {l s='Wellcome to change' mod='nimblepayment'}</h1>
    <h2 class="subtitle">{$subtitle|escape:'htmlall':'UTF-8'}</h2>
</div >
{if !$gateway_enabled}
    <div class="nimbleStepBox step1">
        <h3>{l s='Paso 1: Alta en Nimble Payments' mod='nimblepayment'}</h3>
        <img class="logo" alt="logo-alta" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/oval.png"/>
        <div class="contentStep">
            <p class="subtitle">{l s='Registraté y date de alta, es necesario para poder crear pasarelas de pago.' mod='nimblepayment'}</p>
            <p>{l s='Solo necesitas un email y una contraseña para empezar a probar.' mod='nimblepayment'}
                <img class="link" alt="logo-alta-link" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/edit.png"/>
                <a class="btn" href="https://www.nimblepayments.com/private/registration?utm_source=Prestashop_BackOffice&utm_medium=Referral%20Partners&utm_campaign=Creacion-Cuenta&partner=prestashop" target="_blank" class="link">{l s='Try now!' mod='nimblepayment'}</a>
            </p>
        </div>
    </div>
    <div class="nimbleStepBox step2">
        <h3>{l s='Paso 2: Configura tu modulo' mod='nimblepayment'}</h3>
        <img class="logo" alt="logo-alta" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/oval.png"/>
        <div class="contentStep">
            <p class="subtitle">{l s='Necesitamos las credenciales de tu pasarela de pago en Nimble Payments.' mod='nimblepayment'}</p>
            <p>{l s='¿No los tienes a mano?' mod='nimblepayment'}
                <img class="link" alt="logo-alta-link" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/edit.png"/>
                <a class="btn" onclick="window.open('{$url_nimble|escape:'htmlall':'UTF-8'}', '', 'width=800, height=578')">{l s='Consultalos aquí' mod='nimblepayment'}</a>
            </p>
            {if $error_message}
            <div class="module_error alert alert-danger">
                    <button data-dismiss="alert" class="close" type="button">×</button>{l s='Data invalid gateway to accept payments.' mod='nimblepayment'}
            </div>
            {/if}
            <form method="post" action="{$post_url|escape:'htmlall':'UTF-8'}">
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
                        <input type="text" class="" value="{$clientSecret|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_SECRET" name="NIMBLEPAYMENT_CLIENT_SECRET">
                    </div>
                </div>
                <div class="form-footer">
                    <button name="saveCredentials" id="module_form_submit_btn" value="1" type="submit">{l s='Save'}</button>
                </div>
            </form>
        </div>
    </div>
{else}
    <div class="nimbleStepBox">
        <h3>{l s='Estos son tus datos' mod='nimblepayment'}</h3>
        <div class="contentStep">
            <form method="post" action="{$post_url|escape:'htmlall':'UTF-8'}">
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
                        <input type="text" class="" value="{$clientSecret|escape:'htmlall':'UTF-8'}" id="NIMBLEPAYMENT_CLIENT_SECRET" name="NIMBLEPAYMENT_CLIENT_SECRET">
                    </div>
                </div>
                <div class="form-footer">
                    <button name="saveCredentials" id="module_form_submit_btn" value="1" type="submit">{l s='Save'}</button>
                </div>
            </form>
        </div>
    </div>
    {if !$authorized}   
        <div class="nimbleAuthorize">
            <div class="title">
                <p>{l s='Hazlo todo sin salir de prestashop:' mod='nimblepayment'}<br/>
                {l s='Gestionar ventas, ver movimientos de tu cuenta, hacer devoluciones, ...' mod='nimblepayment'}</p>
            </div>
            <div class="separator"></div>
            <div class="box">
                <p>{l s='Para ello necesitaremos que te identifiques y nos des permiso para acceder a tus datos' mod='nimblepayment'}</p>
            </div>
            <div class="box">
                <a id="authorize_btn" href="{$Oauth3Url}" class="link">{l s='Authorize Prestashop' mod='nimblepayment'}</a>
            </div>
        </div>
    {else}
        <div class="nimbleAuthorize">
            <div class="box">
                <img class="logo" alt="logo-authorized" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/oval.png"/>
                <p>{l s='Tienda autorizada para operar en Nimble Payments' mod='nimblepayment'}</p>
            </div>
            <div class="box">
                <form method="post" action="{$post_url|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" value="1" name="removeOauth2">
                        <button name="removeOauth2" id="unauthorize_btn" value="1" type="submit">{l s='Desvincular'}</button>
                </form>
            </div>
        </div>
    {/if}
{/if}