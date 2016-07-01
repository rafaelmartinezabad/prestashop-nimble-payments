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
*  @author    Devtopia Coop <hello@devtopia.coop>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
 
<link href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/nimble.css" rel="stylesheet" type="text/css" media="all">

<div class="row">
	<div class="col-lg-12">
		<div class="panel">
                    <div class="text">{l s='You have not yet Prestashop authorized to perform operations on Nimble Payments.' mod='nimblepayment'}
                        <p class="btn">
                            <a href="{$Oauth3Url}" class="btn btn-primary link">{l s='Authorize Prestashop' mod='nimblepayment'}</a>
                        </p>
                    </div>
                </div>
	</div>
</div>