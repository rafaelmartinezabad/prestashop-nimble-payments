{*
/**
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
 *     @author    PrestaShop SA <contact@prestashop.com>
 *     @copyright 2007-2015 PrestaShop SA
 *     @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
 *}

<link href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/nimblebackend.css" rel="stylesheet" type="text/css" media="all">

<section id="dashnimbleactivity" class="panel widget">
        <div class="panel-heading">
	       <i class="icon-AdminNimble"></i> {l s='Nimble Payments Account' mod='nimblepayment'}
		<span class="panel-heading-action">
			<a class="list-toolbar-btn" href="#" onclick="toggleDashConfig('dashactivity'); return false;" title="Configurar">
				<i class="process-icon-configure"></i>
			</a>
			<a class="list-toolbar-btn" href="#" onclick="refreshDashboard('dashactivity'); return false;" title="Refrescar">
				<i class="process-icon-refresh"></i>
			</a>
		</span>
	</div>
	<section>
                {if $token == false}
                    <div class="text">
                            <p>{l s='From Prestashop you can manage all ypur sales, see the movements of your account, make refunds, etc.' mod='nimblepayment'}</p>
                            <p>{l s='To release all features of Nimble Payments from Prestashop, you need to login in Nimble Payments and grant access to Prestashop in order to access to this operative.' mod='nimblepayment'}</p>                   
                    </div>
                    <div class="btn-autorize">
                        <a href="{$Oauth3Url}" class="btn btn-nimble link">{l s='Authorize Prestashop' mod='nimblepayment'}</a>
                    </div>    
                {else}
                    <ul class="data_list_vertical">
			<li>
				<span class="data_label size_md nimble-text">{l s='Balance account' mod='nimblepayment'}</span>
				<span id="nimble-balance">{$balance_str|escape:'htmlall':'UTF-8'}</span>
			</li>
			<li>
				<span class="data_label size_md nimble-text">{l s='Total available' mod='nimblepayment'}</span>
			        <span id="nimble-total-available">{$total_str|escape:'htmlall':'UTF-8'}</span>
			</li>
		    </ul>        
                {/if}
	</section>
</section>
